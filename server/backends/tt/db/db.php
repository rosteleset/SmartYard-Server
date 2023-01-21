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
                    $projects = $this->db->query("select project_id, acronym, project, max_file_size, mime_types from tt_projects order by acronym", \PDO::FETCH_ASSOC)->fetchAll();
                    $_projects = [];

                    foreach ($projects as $project) {
                        $workflows = $this->db->query("select workflow from tt_projects_workflows where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $w = [];
                        foreach ($workflows as $workflow) {
                            $w[] = $workflow['workflow'];
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

                        $groups = $this->db->query("select project_role_id, gid, role_id from tt_projects_roles where project_id = {$project["project_id"]} and gid is not null");
                        foreach ($groups as $group) {
                            $g[] = [
                                "projectRoleId" => $group["project_role_id"],
                                "gid" => $group["gid"],
                                "roleId" => $group["role_id"],
                            ];
                            $users = $this->db->query("select uid from core_users_groups where gid = {$group["gid"]}");
                            foreach ($users as $user) {
                                $f = false;
                                foreach ($u as &$_u) {
                                    if ($_u["uid"] == $user["uid"]) {
                                        if ($_u["roleId"] < $group["role_id"]) {
                                            $_u["projectRoleId"] = $group["project_role_id"];
                                            $_u["roleId"] = $group["role_id"];
                                            $_u["byGroup"] = true;
                                            $_u["from"] = 4;
                                        }
                                        $f = true;
                                    }
                                }
                                if (!$f) {
                                    $u[] = [
                                        "projectRoleId" => $group["project_role_id"],
                                        "uid" => $user["uid"],
                                        "roleId" => $group["role_id"],
                                        "byGroup" => true,
                                        "from" => 3,
                                    ];
                                }
                            }
                        }

                        $users = $this->db->query("select project_role_id, uid, role_id from tt_projects_roles where project_id = {$project["project_id"]} and uid is not null");
                        foreach ($users as $user) {
                            $f = false;
                            foreach ($u as &$_u) {
                                if ($_u["uid"] == $user["uid"]) {
                                    $_u["projectRoleId"] = $user["project_role_id"];
                                    $_u["roleId"] = $user["role_id"];
                                    $_u["byGroup"] = false;
                                    $_u["from"] = 2;
                                    $f = true;
                                }
                            }
                            if (!$f) {
                                $u[] = [
                                    "projectRoleId" => $user["project_role_id"],
                                    "uid" => $user["uid"],
                                    "roleId" => $user["role_id"],
                                    "byGroup" => false,
                                    "from" => 1,
                                ];
                            }
                        }

                        $_projects[] = [
                            "projectId" => $project["project_id"],
                            "acronym" => $project["acronym"],
                            "project" => $project["project"],
                            "maxFileSize" => $project["max_file_size"],
                            "allowedMimeTypes" => $project["mime_types"],
                            "workflows" => $w,
                            "resolutions" => $r,
                            "customFields" => $cf,
                            "users" => $u,
                            "groups" => $g,
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
            public function modifyProject($projectId, $acronym, $project, $maxFileSize, $allowedMimeTypes)
            {
                if (!checkInt($projectId) || !trim($acronym) || !trim($project) || !checkInt($maxFileSize)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_projects set acronym = :acronym, project = :project, max_file_size = :max_file_size, mime_types = :mime_types where project_id = $projectId");
                    $sth->execute([
                        "acronym" => $acronym,
                        "project" => $project,
                        "max_file_size" => $maxFileSize,
                        "mime_types" => $allowedMimeTypes,
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
            public function getWorkflowAliases()
            {
                try {
                    $workflows = $this->db->query("select workflow, alias from tt_workflows_aliases order by workflow", \PDO::FETCH_ASSOC)->fetchAll();
                    $_workflows = [];

                    foreach ($workflows as $workflow) {
                        $_workflows[] = [
                            "workflow" => $workflow["workflow"],
                            "alias" => $workflow["alias"],
                        ];
                    }

                    return $_workflows;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function setWorkflowAlias($workflow, $alias)
            {
                $alias = trim($alias);

                if (!$alias) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_workflows_aliases (workflow) values (:workflow)");
                    $sth->execute([
                        ":workflow" => $workflow,
                    ]);
                } catch (\Exception $e) {
//                    error_log(print_r($e, true));
                }

                try {
                    $sth = $this->db->prepare("update tt_workflows_aliases set alias = :alias where workflow = :workflow");
                    $sth->execute([
                        ":workflow" => $workflow,
                        ":alias" => $alias,
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
                    $resolutions = $this->db->query("select issue_resolution_id, resolution from tt_issue_resolutions order by resolution", \PDO::FETCH_ASSOC)->fetchAll();
                    $_resolutions = [];

                    foreach ($resolutions as $resolution) {
                        $_resolutions[] = [
                            "resolutionId" => $resolution["issue_resolution_id"],
                            "resolution" => $resolution["resolution"],
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
            public function addResolution($resolution)
            {
                $resolution = trim($resolution);

                if (!$resolution) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_issue_resolutions (resolution) values (:resolution)");
                    if (!$sth->execute([
                        ":resolution" => $resolution,
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
            public function modifyResolution($resolutionId, $resolution)
            {
                $resolution = trim($resolution);

                if (!checkInt($resolutionId) || !$resolution) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_issue_resolutions set resolution = :resolution where issue_resolution_id = $resolutionId");
                    $sth->execute([
                        ":resolution" => $resolution,
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
            public function deleteResolution($resolutionId)
            {
                if (!checkInt($resolutionId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_issue_resolutions where issue_resolution_id = $resolutionId");
                    $this->db->exec("delete from tt_projects_resolutions where issue_resolution_id = $resolutionId");
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
                    $customFields = $this->db->query("select issue_custom_field_id, type, workflow, field, field_display, field_description, regex, link, format, editor, indexes, required from tt_issue_custom_fields order by field", \PDO::FETCH_ASSOC)->fetchAll();
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
                            "indexes" => $customField["indexes"],
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

                try {
                    $this->db->exec("insert into tt_projects_roles (project_id, uid, role_id) values ($projectId, $uid, $roleId)");
                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function addGroupRole($projectId, $gid, $roleId)
            {
                if (!checkInt($projectId) || !checkInt($gid) || !checkInt($roleId)) {
                    return false;
                }

                try {
                    $this->db->exec("insert into tt_projects_roles (project_id, gid, role_id) values ($projectId, $gid, $roleId)");
                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function getRoles()
            {
                try {
                    $roles = $this->db->query("select role_id, name, name_display, level from tt_roles order by level", \PDO::FETCH_ASSOC)->fetchAll();
                    $_roles = [];

                    foreach ($roles as $role) {
                        $_roles[] = [
                            "roleId" => $role["role_id"],
                            "name" => $role["name"],
                            "nameDisplay" => $role["name_display"],
                            "level" => $role["level"],
                        ];
                    }

                    return $_roles;
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
            public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indexes, $required, $editor)
            {
                if (!checkInt($customFieldId)) {
                    return false;
                }

                if (!checkInt($indexes)) {
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
                                indexes = :indexes,
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
                            ":indexes" => $indexes,
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
                        $this->db->modify("delete from tt_issue_custom_fields where issue_custom_field_id = $customFieldId");
                        $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId");
                        $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id = $customFieldId");
                        return true;
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
            public function searchIssues($by, $query)
            {
                // TODO: Implement searchIssues() method.
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
            public function filterAvailable($filter)
            {
                return $this->db->get("select filter_available_id, uid, gid from tt_filters_available order by uid, gid", false, [
                    "filter_available_id" => "filterAvailableId",
                    "uid" => "uid",
                    "gid" => "gid",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addFilterAvailable($filter, $uid, $gid)
            {
                $uid = $uid ? : null;
                $gid = $gid ? : null;

                return $this->db->insert("insert into tt_filters_available (filter, uid, gid) values (:filter, :uid, :gid)", [
                    "filter" => $filter,
                    "uid" => $uid,
                    "gid" => $gid,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteFilterAvailable($filter_available_id)
            {
                $filter_available_id = (int)$filter_available_id;

                return $this->db->modify("delete from tt_filters_available where filter_available_id = :filter_available_id", [
                    "filter_available_id" => $filter_available_id,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function whoAmI()
            {
                global $params;

                $uid = $params["_uid"];

                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($uid);
                }

                $projects = [];

                if ($groups) {
                    $g = [];

                    foreach ($groups as $group) {
                        $g[] = $group["gid"];
                    }

                    $g = implode(",", $g);

                    $groups = $this->db->get("select project_id, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where gid in ($g)", false, [
                        "level" => "level",
                        "project_id" => "projectId",
                    ]);

                    foreach ($groups as $group) {
                        $projects[$group["projectId"]] = max(@(int)$projects[$group["projectId"]], (int)$group["level"]);
                    }
                }

                $levels = $this->db->get("select project_id, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where uid = $uid", false, [
                    "level" => "level",
                    "project_id" => "projectId",
                ]);

                foreach ($levels as $level) {
                    $projects[$level["projectId"]] = min(@(int)$projects[$level["projectId"]], (int)$level["level"]);
                }

                return $projects;
            }

            /**
             * @inheritDoc
             */
            public function myFilters()
            {
                global $params;

                $uid = $params["_uid"];

                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($uid);
                }

                if ($groups) {
                    $g = [];

                    foreach ($groups as $group) {
                        $g[] = $group["gid"];
                    }

                    $g = implode(",", $g);

                    $filters = $this->db->get("select filter from tt_filters_available where uid = $uid or gid in ($g)", false, [
                        "filter" => "filter",
                    ]);
                } else {
                    $filters = $this->db->get("select filter from tt_filters_available where uid = $uid", false, [
                        "filter" => "filter",
                    ]);
                }

                $f = [];
                foreach ($filters as $filter) {
                    $f[] = $this->getFilter($filter["filter"]);
                }

                return $f;
            }

            /**
             * @inheritDoc
             */
            public function cleanup() {
                $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");

                parent::cleanup();
            }
        }
    }

