<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /*
         * common part for all tt classes based on internal database storage for tt metadata
         */

        trait db {

            /**
             * @inheritDoc
             */
            public function allow($params)
            {
                return true;
            }

            /**
             * @inheritDoc
             */
            public function capabilities()
            {
                return [
                    "mode" => "rw",
                ];
            }

            /**
             * @inheritDoc
             */
            public function getProjects()
            {
                try {
                    $projects = $this->db->query("select project_id, acronym, project, max_file_size, mime_types, search_subject, search_description, search_comments from tt_projects order by acronym", \PDO::FETCH_ASSOC)->fetchAll();
                    $_projects = [];

                    foreach ($projects as $project) {
                        $workflows = $this->db->query("select workflow from tt_projects_workflows where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $w = [];
                        foreach ($workflows as $workflow) {
                            $w[] = $workflow['workflow'];
                        }

                        $filters = $this->db->query("select filter from tt_projects_filters where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $f = [];
                        foreach ($filters as $filter) {
                            $f[] = $filter['filter'];
                        }

                        $resolutions = $this->db->query("select issue_resolution_id from tt_projects_resolutions where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $r = [];
                        foreach ($resolutions as $resolution) {
                            $r[] = $resolution["issue_resolution_id"];
                        }

                        $customFields = $this->db->query("select issue_custom_field_id from tt_projects_custom_fields where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $cf = [];
                        foreach ($customFields as $customField) {
                            $cf[] = $customField["issue_custom_field_id"];
                        }

                        $u = [];
                        $g = [];

                        $groups = $this->db->query("select project_role_id, gid, role_id, level from tt_projects_roles left join tt_roles using (role_id) where project_id = {$project["project_id"]} and gid is not null");
                        foreach ($groups as $group) {
                            $g[] = [
                                "projectRoleId" => $group["project_role_id"],
                                "gid" => $group["gid"],
                                "roleId" => $group["role_id"],
                            ];
                            $users = $this->db->query("select uid from core_users_groups where gid = {$group["gid"]}");
                            foreach ($users as $user) {
                                $_f = false;
                                foreach ($u as &$_u) {
                                    if ($_u["uid"] == $user["uid"]) {
                                        if ($_u["roleId"] < $group["role_id"]) {
                                            $_u["projectRoleId"] = $group["project_role_id"];
                                            $_u["roleId"] = $group["role_id"];
                                            $_u["level"] = $group["level"];
                                            $_u["byGroup"] = true;
                                        }
                                        $_f = true;
                                    }
                                }
                                if (!$_f) {
                                    $u[] = [
                                        "projectRoleId" => $group["project_role_id"],
                                        "uid" => $user["uid"],
                                        "roleId" => $group["role_id"],
                                        "level" => $group["level"],
                                        "byGroup" => true,
                                    ];
                                }
                            }
                        }

                        $users = $this->db->query("select project_role_id, uid, role_id, level from tt_projects_roles left join tt_roles using (role_id) where project_id = {$project["project_id"]} and uid is not null and uid > 0");
                        foreach ($users as $user) {
                            $_f = false;
                            foreach ($u as &$_u) {
                                if ($_u["uid"] == $user["uid"]) {
                                    $_u["projectRoleId"] = $user["project_role_id"];
                                    $_u["roleId"] = $user["role_id"];
                                    $_u["level"] = $user["level"];
                                    $_u["byGroup"] = false;
                                    $_f = true;
                                }
                            }
                            if (!$_f) {
                                $u[] = [
                                    "projectRoleId" => $user["project_role_id"],
                                    "uid" => $user["uid"],
                                    "roleId" => $user["role_id"],
                                    "level" => $user["level"],
                                    "byGroup" => false,
                                ];
                            }
                        }

                        $_projects[] = [
                            "projectId" => $project["project_id"],
                            "acronym" => $project["acronym"],
                            "project" => $project["project"],
                            "maxFileSize" => $project["max_file_size"],
                            "allowedMimeTypes" => $project["mime_types"],
                            "searchSubject" => $project["search_subject"],
                            "searchDescription" => $project["search_description"],
                            "searchComments" => $project["search_comments"],
                            "workflows" => $w,
                            "filters" => $f,
                            "resolutions" => $r,
                            "customFields" => $cf,
                            "users" => $u,
                            "groups" => $g,
                            "viewers" => $this->getProjectViewers($project["project_id"]),
                        ];
                    }

                    return $_projects;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function addProject($acronym, $project)
            {
                $acronym = trim($acronym);
                $project = trim($project);

                if (!$acronym || !$project) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects (acronym, project) values (:acronym, :project)");
                    if (!$sth->execute([
                        ":acronym" => $acronym,
                        ":project" => $project,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function modifyProject($projectId, $acronym, $project, $maxFileSize, $allowedMimeTypes, $searchSubject, $searchDescription, $searchComments)
            {
                if (!checkInt($projectId) || !trim($acronym) || !trim($project) || !checkInt($maxFileSize) || !checkInt($searchSubject) || !checkInt($searchDescription) || !checkInt($searchComments)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_projects set acronym = :acronym, project = :project, max_file_size = :max_file_size, mime_types = :mime_types, search_subject = :search_subject, search_description = :search_description, search_comments = :search_comments where project_id = $projectId");
                    $sth->execute([
                        "acronym" => $acronym,
                        "project" => $project,
                        "max_file_size" => $maxFileSize,
                        "mime_types" => $allowedMimeTypes,
                        "search_subject" => $searchSubject,
                        "search_description" => $searchDescription,
                        "search_comments" => $searchComments,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function deleteProject($projectId)
            {
                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects where project_id = $projectId");
                    // TODO: delete all derivatives
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function setProjectWorkflows($projectId, $workflows)
            {
                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_workflows (project_id, workflow) values (:project_id, :workflow)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_workflows where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($workflows as $workflow) {
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":workflow" => $workflow,
                        ])) {
                            return false;
                        }
                        $w = $this->loadWorkflow($workflow);
                        if (!$w->initProject($projectId)) {
                            setLastError("invalidWorflow");
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    setLastError("invalidWorflow");
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function setProjectFilters($projectId, $filters)
            {
                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_filters (project_id, filter) values (:project_id, :filter)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_filters where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                foreach ($filters as $filter) {
                    if (!$sth->execute([
                        ":project_id" => $projectId,
                        ":filter" => $filter,
                    ])) {
                        return false;
                    }
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function deleteWorkflow($workflow) {
                parent::deleteWorkflow($workflow);

                $this->db->modify("delete from tt_projects_workflows where workflow = :workflow", [
                    "workflow" => $workflow,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteFilter($filter) {
                parent::deleteFilter($filter);

                $this->db->modify("delete from tt_projects_filters where filter = :filter", [
                    "filter" => $filter,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getStatuses()
            {
                try {
                    $statuses = $this->db->query("select issue_status_id, status, status_display from tt_issue_statuses order by status", \PDO::FETCH_ASSOC)->fetchAll();
                    $_statuses = [];

                    foreach ($statuses as $statuse) {
                        $_statuses[] = [
                            "statusId" => $statuse["issue_status_id"],
                            "status" => $statuse["status"],
                            "statusDisplay" => $statuse["status_display"],
                        ];
                    }

                    return $_statuses;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function moodifyStatus($statusId, $display)
            {
                $display = trim($display);

                if (!checkInt($statusId) || !$display) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_issue_statuses set status_display = :status_display where issue_status_id = $statusId");
                    $sth->execute([
                        ":status_display" => $display,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getResolutions()
            {
                try {
                    $resolutions = $this->db->query("select issue_resolution_id, resolution, protected, alias from tt_issue_resolutions order by resolution", \PDO::FETCH_ASSOC)->fetchAll();
                    $_resolutions = [];

                    foreach ($resolutions as $resolution) {
                        $_resolutions[] = [
                            "resolutionId" => $resolution["issue_resolution_id"],
                            "resolution" => $resolution["resolution"],
                            "protected" => $resolution["protected"],
                            "alias" => $resolution["alias"],
                        ];
                    }

                    return $_resolutions;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function addResolution($resolution, $protected = 0)
            {
                $resolution = trim($resolution);

                if (!checkInt($protected)) {
                    return false;
                }

                if (!$resolution) {
                    return false;
                }

                return $this->db->insert("insert into tt_issue_resolutions (resolution, alias, protected) values (:resolution, :resolution, :protected)", [ "resolution" => $resolution, "protected" => $protected ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyResolution($resolutionId, $resolution)
            {
                $resolution = trim($resolution);

                if (!checkInt($resolutionId) || !$resolution) {
                    return false;
                }

                return $this->db->modify("update tt_issue_resolutions set resolution = :resolution where issue_resolution_id = $resolutionId", [ "resolution" => $resolution, ]) +
                    $this->db->modify("update tt_issue_resolutions set alias = :resolution where issue_resolution_id = $resolutionId and protected = 0", [ "resolution" => $resolution, ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteResolution($resolutionId)
            {
                if (!checkInt($resolutionId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_issue_resolutions where issue_resolution_id = $resolutionId and protected = 0") +
                    $this->db->modify("delete from tt_projects_resolutions where issue_resolution_id not in (select issue_resolution_id from tt_issue_resolutions)");
            }

            /**
             * @inheritDoc
             */
            public function setProjectResolutions($projectId, $resolutions)
            {
                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_resolutions (project_id, issue_resolution_id) values (:project_id, :issue_resolution_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_resolutions where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($resolutions as $resolution) {
                        if (!checkInt($resolution)) {
                            return false;
                        }
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":issue_resolution_id" => $resolution,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getCustomFields()
            {
                try {
                    $customFields = $this->db->query("select issue_custom_field_id, type, workflow, field, field_display, field_description, regex, link, format, editor, indx, search, required from tt_issue_custom_fields order by field", \PDO::FETCH_ASSOC)->fetchAll();
                    $_customFields = [];

                    foreach ($customFields as $customField) {
                        $options = $this->db->query("select issue_custom_field_option_id, option, option_display from tt_issue_custom_fields_options where issue_custom_field_id = {$customField["issue_custom_field_id"]} order by display_order", \PDO::FETCH_ASSOC)->fetchAll();
                        $_options = [];

                        foreach ($options as $option) {
                            $_options[] = [
                                "customFieldOptionId" => $option["issue_custom_field_option_id"],
                                "option" => $option["option"],
                                "optionDisplay" => $option["option_display"],
                            ];
                        }

                        $_customFields[] = [
                            "customFieldId" => $customField["issue_custom_field_id"],
                            "type" => $customField["type"],
                            "workflow" => $customField["workflow"],
                            "field" => $customField["field"],
                            "fieldDisplay" => $customField["field_display"],
                            "fieldDescription" => $customField["field_description"],
                            "regex" => $customField["regex"],
                            "link" => $customField["link"],
                            "format" => $customField["format"],
                            "editor" => $customField["editor"],
                            "indx" => $customField["indx"],
                            "search" => $customField["search"],
                            "required" => $customField["required"],
                            "options" => $_options,
                        ];
                    }

                    return $_customFields;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function addCustomField($type, $field, $fieldDisplay)
            {
                $type = trim($type);
                $field = trim($field);
                $fieldDisplay = trim($fieldDisplay);

                if (!$type || !$field || !$fieldDisplay) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("
                        insert into 
                            tt_issue_custom_fields (type, field, field_display, workflow)
                        values (:type, :field, :field_display, 0)");
                    if (!$sth->execute([
                        ":type" => $type,
                        ":field" => $field,
                        ":field_display" => $fieldDisplay,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function setProjectCustomFields($projectId, $customFields)
            {
                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_custom_fields (project_id, issue_custom_field_id) values (:project_id, :issue_custom_field_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_custom_fields where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($customFields as $customField) {
                        if (!checkInt($customField)) {
                            return false;
                        }
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":issue_custom_field_id" => $customField,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function addUserRole($projectId, $uid, $roleId)
            {
                if (!checkInt($projectId) || !checkInt($uid) || !checkInt($roleId)) {
                    return false;
                }

                return $this->db->insert("insert into tt_projects_roles (project_id, uid, role_id) values ($projectId, $uid, $roleId)");
            }

            /**
             * @inheritDoc
             */
            public function addGroupRole($projectId, $gid, $roleId)
            {
                if (!checkInt($projectId) || !checkInt($gid) || !checkInt($roleId)) {
                    return false;
                }

                $positive = $this->db->get("select count(*) from tt_roles where role_id = $roleId and level > 0", false, false, [ "fieldlify" ]);

                if ($positive) {
                    return $this->db->insert("insert into tt_projects_roles (project_id, gid, role_id) values ($projectId, $gid, $roleId)");
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function getRoles()
            {
                try {
                    return $this->db->get("select role_id, name, name_display, level from tt_roles order by level", false, [
                        "role_id" => "roleId",
                        "name" => "name",
                        "name_display" => "nameDisplay",
                        "level" => "level"
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function deleteRole($projectRoleId)
            {
                if (!checkInt($projectRoleId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_roles where project_role_id = $projectRoleId");

                    return true;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function setRoleDisplay($roleId, $nameDisplay)
            {
                $nameDisplay = trim($nameDisplay);

                if (!checkInt($roleId) || !$nameDisplay) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_roles set name_display = :name_display where role_id = $roleId");
                    $sth->execute([
                        ":name_display" => $nameDisplay,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor)
            {
                if (!checkInt($customFieldId)) {
                    return false;
                }

                if (!checkInt($indx)) {
                    return false;
                }

                if (!checkInt($search)) {
                    return false;
                }

                if (!checkInt($required)) {
                    return false;
                }

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                try {
                    if ($cf["workflow"]) {
                        $sth = $this->db->prepare("
                                update
                                    tt_issue_custom_fields
                                set 
                                    field_display = :field_display,
                                    field_description = :field_description,
                                    link = :link
                                where
                                    issue_custom_field_id = $customFieldId
                            ");
                        $sth->execute([
                            ":field_display" => $fieldDisplay,
                            ":field_description" => $fieldDescription,
                            ":link" => $link,
                        ]);

                        $upd = $this->db->prepare("update tt_issue_custom_fields_options set option_display = :display where issue_custom_field_id = $customFieldId and issue_custom_field_option_id = :option");

                        foreach ($options as $option => $display) {
                            if (!checkInt($option)) {
                                return false;
                            }
                            $upd->execute([
                                ":option" => $option,
                                ":display" => $display,
                            ]);
                        }
                    } else {
                        $sth = $this->db->prepare("
                            update
                                tt_issue_custom_fields
                            set 
                                field_display = :field_display,
                                field_description = :field_description,
                                regex = :regex,
                                link = :link,
                                format = :format,
                                editor = :editor,
                                indx = :indx,
                                required = :required
                            where
                                issue_custom_field_id = $customFieldId
                        ");

                        $sth->execute([
                            ":field_display" => $fieldDisplay,
                            ":field_description" => $fieldDescription,
                            ":regex" => $regex,
                            ":link" => $link,
                            ":format" => $format,
                            ":editor" => $editor,
                            ":indx" => $indx,
                            ":search" => $search,
                            ":required" => $required,
                        ]);

                        // TODO: create and remove indexes

                        if ($cf["type"] === "Select" || $cf["type"] === "MultiSelect") {
                            $t = explode("\n", trim($options));
                            $new = [];
                            foreach ($t as $i) {
                                $i = trim($i);
                                if ($i) {
                                    $new[] = $i;
                                }
                            }

                            $ins = $this->db->prepare("insert into tt_issue_custom_fields_options (issue_custom_field_id, option, option_display) values ($customFieldId, :option, :option)");
                            $del = $this->db->prepare("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId and option = :option");
                            $upd = $this->db->prepare("update tt_issue_custom_fields_options set option_display = :option, display_order = :order where issue_custom_field_id = $customFieldId and option = :option");

                            $options = $this->db->query("select option from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                            $old = [];
                            foreach ($options as $option) {
                                $old[] = $option["option"];
                            }

                            foreach ($old as $j) {
                                $f = false;
                                foreach ($new as $i) {
                                    if ($i == $j) {
                                        $f = true;
                                        break;
                                    }
                                }
                                if (!$f) {
                                    $del->execute([
                                        ":option" => $j,
                                    ]);
                                }
                            }

                            foreach ($new as $j) {
                                $f = false;
                                foreach ($old as $i) {
                                    if ($i == $j) {
                                        $f = true;
                                        break;
                                    }
                                }
                                if (!$f) {
                                    $ins->execute([
                                        ":option" => $j,
                                    ]);
                                }
                            }

                            $n = 1;
                            foreach ($new as $j) {
                                $upd->execute([
                                    ":option" => $j,
                                    ":order" => $n,
                                ]);
                                $n++;
                            }

                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function deleteCustomField($customFieldId)
            {
                if (!checkInt($customFieldId)) {
                    return false;
                }

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                if ($cf["workflow"]) {
                    return false;
                } else {
                    try {
                        return $this->db->modify("delete from tt_issue_custom_fields where issue_custom_field_id = $customFieldId") +
                            $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId") +
                            $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id = $customFieldId") +
                            $this->db->modify("delete from tt_viewers where field = '[cf]' || :field", [
                                "field" => $cf['field'],
                            ]);
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                        return false;
                    }
                }
            }

            /**
             * @inheritDoc
             */
            public function getTags()
            {
                return $this->db->get("select * from tt_tags order by tag", false, [
                    "tag_id" => "tagId",
                    "project_id" => "projectId",
                    "tag" => "tag",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addTag($projectId, $tag)
            {
                if (!checkInt($projectId) || !checkStr($tag)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_tags (project_id, tag) values (:project_id, :tag)");
                    if (!$sth->execute([
                        "project_id" => $projectId,
                        "tag" => $tag,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function modifyTag($tagId, $tag)
            {
                if (!checkInt($tagId) || !checkStr($tag)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_tags set tag = :tag where tag_id = $tagId");
                    $sth->execute([
                        "tag" => $tag,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function deleteTag($tagId)
            {
                if (!checkInt($tagId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_tags where tag_id = $tagId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function myRoles()
            {
                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($this->uid);
                }

                $projects = [];

                if ($groups) {
                    $g = [];

                    foreach ($groups as $group) {
                        $g[] = $group["gid"];
                    }

                    $g = implode(",", $g);

                    $groups = $this->db->get("select acronym, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where gid in ($g) order by level", false, [
                        "level" => "level",
                        "acronym" => "acronym",
                    ]);

                    foreach ($groups as $group) {
                        if (@(int)$projects[$group["acronym"]]) {
                            $projects[$group["acronym"]] = max(@(int)$projects[$group["acronym"]], (int)$group["level"]);
                        } else {
                            $projects[$group["acronym"]] = (int)$group["level"];
                        }
                    }
                }

                $levels = $this->db->get("select acronym, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where uid = {$this->uid} and uid > 0 order by level", false, [
                    "level" => "level",
                    "acronym" => "acronym",
                ]);

                foreach ($levels as $level) {
                    if (@(int)$projects[$level["acronym"]]) {
                        if ((int)$level["level"] > 0) {
                            $projects[$level["acronym"]] = min(@(int)$projects[$level["acronym"]], (int)$level["level"]);
                        } else {
                            unset($projects[$level["acronym"]]);
                        }
                    } else {
                        if ((int)$level["level"] > 0) {
                            $projects[$level["acronym"]] = (int)$level["level"];
                        }
                    }
                }

                return $projects;
            }

            /**
             * @inheritDoc
             */
            public function myGroups()
            {
                $groups = loadBackend("groups");

                $g = [];

                if ($groups) {
                    $groups = $groups->getGroups($this->uid);


                    foreach ($groups as $group) {
                        $g[] = $group["acronym"];
                    }
                }

                return $g;
            }

            /**
             * @inheritDoc
             */
            public function myFilters()
            {
                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($this->uid);
                }

                if ($groups) {
                    $g = [];

                    foreach ($groups as $group) {
                        $g[] = $group["gid"];
                    }

                    $g = implode(",", $g);

                    $filters = $this->db->get("select filter from tt_filters_available where (uid = {$this->uid} and uid > 0) or gid in ($g)", false, [
                        "filter" => "filter",
                    ]);
                } else {
                    $filters = $this->db->get("select filter from tt_filters_available where uid = {$this->uid} and uid > 0", false, [
                        "filter" => "filter",
                    ]);
                }

                $f = [];
                foreach ($filters as $filter) {
                    $f[$filter["filter"]] = @json_decode($this->getFilter($filter["filter"]), true)["name"] ? : $filter["filter"];
                }

                return $f;
            }

            /**
             * @inheritDoc
             */
            public function addViewer($field, $name) {
                if (!checkStr($name) || !checkStr($field)) {
                    return false;
                }

                return $this->db->insert("insert into tt_viewers (name, field) values (:name, :field)", [
                    "name" => $name,
                    "field" => $field,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyViewer($field, $name, $code) {
                if (!checkStr($field) || !checkStr($name)) {
                    return false;
                }

                return $this->db->modify("update tt_viewers set code = :code where field = :field and name = :name", [
                    "field" => $field,
                    "name" => $name,
                    "code" => $code,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteViewer($field, $name) {
                if (!checkStr($field) || !checkStr($name)) {
                    return false;
                }

                return $this->db->modify("delete from tt_viewers where field = :field and name = :name", [
                        "field" => $field,
                        "name" => $name,
                    ]) +
                    $this->db->modify("delete from tt_projects_viewers where project_view_id in (select project_view_id from tt_projects_viewers left join tt_viewers on tt_projects_viewers.field = tt_viewers.field and tt_projects_viewers.name = tt_viewers.name where code is null)");
            }

            /**
             * @inheritDoc
             */
            public function getViewers() {
                return $this->db->get("select * from tt_viewers order by name", false, [
                    "name" => "name",
                    "field" => "field",
                    "code" => "code",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getProjectViewers($projectId) {
                if (!checkInt($projectId)) {
                    return false;
                }

                return $this->db->get("select field, name from tt_projects_viewers where project_id = $projectId group by name order by name");
            }

            /**
             * @inheritDoc
             */
            public function setProjectViewers($projectId, $viewers) {
                if (!checkInt($projectId)) {
                    return false;
                }

                $n = $this->db->modify("delete from tt_projects_viewers where project_id = $projectId");

                foreach ($viewers as $viewer) {
                    $n += $this->db->insert("insert into tt_projects_viewers (project_id, field, name) values (:project_id, :field, :name)", [
                        "project_id" => $projectId,
                        "field" => $viewer["field"],
                        "name" => $viewer["name"],
                    ]);
                }

                return $n;
            }

            /**
             * @inheritDoc
             */
            public function getCrontabs() {
                return $this->db->get("select * from tt_crontabs order by crontab, filter, action", false, [
                    "crontab_id" => "crontabId",
                    "crontab" => "crontab",
                    "project_id" => "projectId",
                    "filter" => "filter",
                    "uid" => "uid",
                    "action" => "action",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addCrontab($crontab, $projectId, $filter, $uid, $action) {
                if (!checkInt($uid)) {
                    return false;
                }

                return $this->db->insert("insert into tt_crontabs (crontab, project_id, filter, uid, action) values (:crontab, :project_id, :filter, :uid, :action)", [
                    "crontab" => $crontab,
                    "project_id" => $projectId,
                    "filter" => $filter,
                    "uid" => $uid,
                    "action" => $action,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteCrontab($crontabId) {
                if (!checkInt($crontabId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_crontabs where crontab_id = $crontabId");
            }

            /**
             * @inheritDoc
             */
            public function cleanup() {
                $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_viewers where field not in (select '[cf]' || field from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_viewers where name not in (select name from tt_viewers)");
                $this->db->modify("delete from tt_projects_viewers where project_id not in (select project_id from tt_projects)");

                parent::cleanup();
            }
        }
    }

