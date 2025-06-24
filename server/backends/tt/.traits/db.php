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

            public function allow($params) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function capabilities() {
                $cap = parent::capabilities();

                if ($cap) {
                    $cap["mode"] = "rw";
                } else {
                    $cap = [
                        "mode" => "rw",
                    ];
                }

                return $cap;
            }

            /**
             * @inheritDoc
             */

            public function getProjects($acronym = false) {
                $key = $acronym ? "PROJECT:$acronym" : "PROJECTS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                try {
                    if ($acronym) {
                        $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments, assigned, comments from tt_projects where acronym = :acronym", [
                            "acronym" => $acronym,
                        ]);
                    } else {
                        $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments, assigned, comments from tt_projects order by acronym");
                    }
                    $_projects = [];

                    foreach ($projects as $project) {
                        $workflows = $this->db->query("select workflow from tt_projects_workflows where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $w = [];
                        foreach ($workflows as $workflow) {
                            $w[] = $workflow['workflow'];
                        }

                        if ($this->uid) {
                            $gids = $this->myGroups(true);
                            foreach ($gids as &$gid) {
                                $gid = 1000000 + (int)$gid;
                            }
                            $gids = implode(",", $gids);
                            if ($gids) {
                                $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} and (personal is null or personal = {$this->uid} or personal in ($gids)) order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                            } else {
                                $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} and (personal is null or personal = {$this->uid}) order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                            }
                        } else {
                            $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                        }

                        $f = [];
                        foreach ($filters as $filter) {
                            $f[] = [
                                "projectFilterId" => $filter['project_filter_id'],
                                "filter" => $filter['filter'],
                                "personal" => $filter['personal'],
                            ];
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

                        $customFieldsNoJournal = $this->db->query("select issue_custom_field_id from tt_projects_custom_fields_nojournal where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $cfnj = [];
                        foreach ($customFieldsNoJournal as $customField) {
                            $cfnj[] = $customField["issue_custom_field_id"];
                        }

                        $u = [];
                        $g = [];

                        $usersBackend = loadBackend("users");
                        $groupsBackend = loadBackend("groups");

                        // precache
                        $usersBackend->getUser(-1, false);

                        $groups = $this->db->query("select project_role_id, gid, role_id, level from tt_projects_roles left join tt_roles using (role_id) where project_id = {$project["project_id"]} and gid is not null and gid > 0");

                        foreach ($groups as $group) {
                            $g[] = [
                                "projectRoleId" => $group["project_role_id"],
                                "gid" => $group["gid"],
                                "roleId" => $group["role_id"],
                                "acronym" => $groupsBackend ? $groupsBackend->getGroup($group["gid"])["acronym"] : null,
                            ];

                            if ($groupsBackend) {
                                $users = $groupsBackend->getUsers($group["gid"]);
                            } else {
                                $users = [];
                            }

                            foreach ($users as $user) {
                                $user = $usersBackend->getUser($user, false);
                                if ($user && $user["uid"] > 0) {
                                    $_f = false;
                                    foreach ($u as &$_u) {
                                        if ($_u["uid"] == $user["uid"]) {
                                            if ($_u["roleId"] < $group["role_id"]) {
                                                $_u["projectRoleId"] = $group["project_role_id"];
                                                $_u["roleId"] = $group["role_id"];
                                                $_u["level"] = $group["level"];
                                                $_u["login"] = $user["login"];
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
                                            "login" => $user["login"],
                                            "byGroup" => true,
                                        ];
                                    }
                                } else {
                                    //
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
                                    $_u["login"] = $usersBackend->getLoginByUid($user["uid"]);
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
                                    "login" => $usersBackend->getLoginByUid($user["uid"]),
                                    "byGroup" => false,
                                ];
                            }
                        }

                        $_projects[] = [
                            "projectId" => $project["project_id"],
                            "acronym" => $project["acronym"],
                            "project" => $project["project"],
                            "maxFileSize" => $project["max_file_size"],
                            "searchSubject" => $project["search_subject"],
                            "searchDescription" => $project["search_description"],
                            "searchComments" => $project["search_comments"],
                            "assigned" => $project["assigned"],
                            "comments" => $project["comments"],
                            "workflows" => $w,
                            "filters" => $f,
                            "resolutions" => $r,
                            "customFields" => $cf,
                            "customFieldsNoJournal" => $cfnj,
                            "users" => $u,
                            "groups" => $g,
                            "viewers" => $this->getProjectViewers($project["project_id"]),
                            "tags" => $this->getTags($project["project_id"]),
                        ];
                    }

                    $this->cacheSet($key, $_projects);

                    return $_projects;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache($key);
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function addProject($acronym, $project)
            {
                $this->clearCache();

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
            public function modifyProject($projectId, $acronym, $project, $maxFileSize, $searchSubject, $searchDescription, $searchComments, $assigned)
            {
                $this->clearCache();

                if (!checkInt($projectId) || !trim($acronym) || !trim($project) || !checkInt($maxFileSize) || !checkInt($searchSubject) || !checkInt($searchDescription) || !checkInt($searchComments) || !checkInt($assigned)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_projects set acronym = :acronym, project = :project, max_file_size = :max_file_size, search_subject = :search_subject, search_description = :search_description, search_comments = :search_comments, assigned = :assigned where project_id = $projectId");
                    $sth->execute([
                        "acronym" => $acronym,
                        "project" => $project,
                        "max_file_size" => $maxFileSize,
                        "search_subject" => $searchSubject,
                        "search_description" => $searchDescription,
                        "search_comments" => $searchComments,
                        "assigned" => $assigned,
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
                $this->clearCache();

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
                $this->clearCache();

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
            public function addProjectFilter($projectId, $filter, $personal)
            {
                $this->clearCache();

                if (!checkInt($projectId) || !checkInt($personal)) {
                    return false;
                }

                if (!$personal) {
                    $already = $this->db->get("select count(*) from tt_projects_filters where project_id = :project_id and filter = :filter and personal is null", [
                        "project_id" => $projectId,
                        "filter" => $filter,
                    ], false, [ "fieldlify" ]);

                    if ($already) {
                        setLastError("filterAlreadyExists");
                        return false;
                    }
                }

                return $this->db->insert("insert into tt_projects_filters (project_id, filter, personal) values (:project_id, :filter, :personal)", [
                    "project_id" => $projectId,
                    "filter" => $filter,
                    "personal" => $personal?$personal:null,
                ], [
                    "silent",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteProjectFilter($projectFilterId)
            {
                $this->clearCache();

                if (!checkInt($projectFilterId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_projects_filters where project_filter_id = $projectFilterId");
            }

            /**
             * @inheritDoc
             */
            public function deleteWorkflow($workflow) {
                $this->clearCache();

                parent::deleteWorkflow($workflow);

                $this->db->modify("delete from tt_projects_workflows where workflow = :workflow", [
                    "workflow" => $workflow,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteFilter($filter, $owner = false) {
                $this->clearCache();

                parent::deleteFilter($filter);

                $this->db->modify("delete from tt_projects_filters where filter = :filter", [
                    "filter" => $filter,
                ]);

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getStatuses()
            {
                $cache = $this->cacheGet("STATUSES");
                if ($cache) {
                    return $cache;
                }

                try {
                    $statuses = $this->db->query("select issue_status_id, status, final from tt_issue_statuses order by status", \PDO::FETCH_ASSOC)->fetchAll();
                    $_statuses = [];

                    foreach ($statuses as $statuse) {
                        $_statuses[] = [
                            "statusId" => $statuse["issue_status_id"],
                            "status" => $statuse["status"],
                            "final" => $statuse["final"],
                        ];
                    }

                    $this->cacheSet("STATUSES", $_statuses);
                    return $_statuses;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("STATUSES");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function addStatus($status, $final)
            {
                $final = (int)$final ? 1 : 0;

                $this->clearCache();

                $status = trim($status);

                if (!$status) {
                    return false;
                }

                return $this->db->insert("insert into tt_issue_statuses (status, final) values (:status, :final)", [ "status" => $status, "final" => $final ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyStatus($statusId, $status, $final)
            {
                $final = (int)$final ? 1 : 0;

                $this->clearCache();

                $status = trim($status);

                if (!checkInt($statusId) || !$status) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_issue_statuses set status = :status, final = :final where issue_status_id = $statusId");
                    $sth->execute([
                        ":status" => $status,
                        ":final" => $final,
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
            public function deleteStatus($statusId)
            {
                $this->clearCache();

                if (!checkInt($statusId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_issue_statuses where issue_status_id = $statusId");
            }

            /**
             * @inheritDoc
             */
            public function getResolutions()
            {
                $cache = $this->cacheGet("RESOLUTIONS");
                if ($cache) {
                    return $cache;
                }

                try {
                    $resolutions = $this->db->query("select issue_resolution_id, resolution from tt_issue_resolutions order by resolution", \PDO::FETCH_ASSOC)->fetchAll();
                    $_resolutions = [];

                    foreach ($resolutions as $resolution) {
                        $_resolutions[] = [
                            "resolutionId" => $resolution["issue_resolution_id"],
                            "resolution" => $resolution["resolution"],
                        ];
                    }

                    $this->cacheSet("RESOLUTIONS", $_resolutions);
                    return $_resolutions;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("RESOLUTIONS");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addResolution($resolution) {
                $this->clearCache();

                $resolution = trim($resolution);

                if (!$resolution) {
                    return false;
                }

                return $this->db->insert("insert into tt_issue_resolutions (resolution) values (:resolution)", [ "resolution" => $resolution, ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyResolution($resolutionId, $resolution) {
                $this->clearCache();

                $resolution = trim($resolution);

                if (!checkInt($resolutionId) || !$resolution) {
                    return false;
                }

                return $this->db->modify("update tt_issue_resolutions set resolution = :resolution where issue_resolution_id = $resolutionId", [ "resolution" => $resolution, ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteResolution($resolutionId) {
                $this->clearCache();

                if (!checkInt($resolutionId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_issue_resolutions where issue_resolution_id = $resolutionId");
            }

            /**
             * @inheritDoc
             */

            public function setProjectResolutions($projectId, $resolutions) {
                $this->clearCache();

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

            public function getCustomFields() {
                $cache = $this->cacheGet("FIELDS");
                if ($cache) {
                    return $cache;
                }

                try {
                    $customFields = $this->db->query("
                        select
                            issue_custom_field_id,
                            catalog,
                            type,
                            field,
                            field_display,
                            field_display_list,
                            field_description,
                            regex,
                            link,
                            format,
                            editor,
                            float,
                            indx,
                            search,
                            required,
                            readonly
                        from
                            tt_issue_custom_fields
                        order by
                            catalog,
                            field
                    ", \PDO::FETCH_ASSOC)->fetchAll();

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
                            "catalog" => $customField["catalog"],
                            "type" => $customField["type"],
                            "field" => $customField["field"],
                            "fieldDisplay" => $customField["field_display"],
                            "fieldDisplayList" => $customField["field_display_list"],
                            "fieldDescription" => $customField["field_description"],
                            "regex" => $customField["regex"],
                            "link" => $customField["link"],
                            "format" => $customField["format"],
                            "editor" => trim($customField["editor"] ? $customField["editor"] : ""),
                            "float" => $customField["float"] ? $customField["float"] : "0",
                            "indx" => $customField["indx"],
                            "search" => $customField["search"],
                            "required" => $customField["required"],
                            "readonly" => $customField["readonly"],
                            "options" => $_options,
                        ];
                    }

                    $this->cacheSet("FIELDS", $_customFields);
                    return $_customFields;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("FIELDS");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addCustomField($catalog, $type, $field, $fieldDisplay, $fieldDisplayList) {
                $this->clearCache();

                $catalog = trim($catalog);
                $type = trim($type);
                $field = trim($field);
                $fieldDisplay = trim($fieldDisplay);

                if (!$type || !$field || !$fieldDisplay) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("
                        insert into
                            tt_issue_custom_fields (catalog, type, field, field_display, field_display_list)
                        values (:catalog, :type, :field, :field_display, :field_display_list)
                    ");

                    if (!$sth->execute([
                        ":catalog" => $catalog,
                        ":type" => $type,
                        ":field" => $field,
                        ":field_display" => $fieldDisplay,
                        ":field_display_list" => $fieldDisplayList,
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
                $this->clearCache();

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
            public function setProjectCustomFieldsNoJournal($projectId, $customFields)
            {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_custom_fields_nojournal (project_id, issue_custom_field_id) values (:project_id, :issue_custom_field_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_custom_fields_nojournal where project_id = $projectId");
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
                $this->clearCache();

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
                $this->clearCache();

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
                $cache = $this->cacheGet("ROLES");
                if ($cache) {
                    return $cache;
                }

                try {
                    $_roles = $this->db->get("select role_id, name, name_display, level from tt_roles order by level", false, [
                        "role_id" => "roleId",
                        "name" => "name",
                        "name_display" => "nameDisplay",
                        "level" => "level"
                    ]);

                    $this->cacheSet("ROLES", $_roles);
                    return $_roles;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("ROLES");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function deleteRole($projectRoleId)
            {
                $this->clearCache();

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
                $this->clearCache();

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

            public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDisplayList, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor, $float, $readonly) {
                $this->clearCache();

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

                if (!checkInt($float)) {
                    return false;
                }

                if (!checkInt($readonly)) {
                    return false;
                }

                $catalog = trim($catalog);

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                try {
                    $sth = $this->db->prepare("
                        update
                            tt_issue_custom_fields
                        set
                            catalog = :catalog,
                            field_display = :field_display,
                            field_display_list = :field_display_list,
                            field_description = :field_description,
                            regex = :regex,
                            link = :link,
                            format = :format,
                            editor = :editor,
                            float = :float,
                            indx = :indx,
                            search = :search,
                            required = :required,
                            readonly = :readonly
                        where
                            issue_custom_field_id = $customFieldId
                    ");

                    $sth->execute([
                        ":catalog" => $catalog,
                        ":field_display" => $fieldDisplay,
                        ":field_display_list" => $fieldDisplayList,
                        ":field_description" => $fieldDescription,
                        ":regex" => $regex,
                        ":link" => $link,
                        ":format" => $format,
                        ":editor" => $editor,
                        ":float" => $float,
                        ":indx" => $indx,
                        ":search" => $search,
                        ":required" => $required,
                        ":readonly" => $readonly,
                    ]);

                    if ($cf["type"] === "select") {
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
                $this->clearCache();

                if (!checkInt($customFieldId)) {
                    return false;
                }

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                try {
                    return $this->db->modify("delete from tt_issue_custom_fields where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_projects_custom_fields_nojournal where issue_custom_field_id = $customFieldId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function getTags($projectId = false)
            {
                $key = $projectId?"TAGS:$projectId":"TAGS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if ($projectId !== false) {
                    if (!checkInt($projectId)) {
                        return false;
                    }

                    $_tags = $this->db->get("select * from tt_tags where project_id = $projectId order by tag", false, [
                        "tag_id" => "tagId",
                        "tag" => "tag",
                        "foreground" => "foreground",
                        "background" => "background",
                    ]);

                    $this->cacheSet($key, $_tags);
                    return $_tags;
                } else {
                    $_tags = $this->db->get("select * from tt_tags order by tag", false, [
                        "tag_id" => "tagId",
                        "project_id" => "projectId",
                        "tag" => "tag",
                        "foreground" => "foreground",
                        "background" => "background",
                    ]);

                    $this->cacheSet($key, $_tags);
                    return $_tags;
                }
            }

            /**
             * @inheritDoc
             */
            public function addTag($projectId, $tag, $foreground, $background)
            {
                $this->clearCache();

                if (!checkInt($projectId) || !checkStr($tag)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_tags (project_id, tag, foreground, background) values (:project_id, :tag, :foreground, :background)");
                    if (!$sth->execute([
                        "project_id" => $projectId,
                        "tag" => $tag,
                        "foreground" => $foreground,
                        "background" => $background,
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
            public function modifyTag($tagId, $tag, $foreground, $background)
            {
                $this->clearCache();

                if (!checkInt($tagId) || !checkStr($tag)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_tags set tag = :tag, foreground = :foreground, background = :background where tag_id = $tagId");
                    $sth->execute([
                        "tag" => $tag,
                        "foreground" => $foreground,
                        "background" => $background,
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
                $this->clearCache();

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
            public function myRoles($uid = false)
            {
                $key = ($uid !== false)?"MYROLES:$uid":"MYROLES";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if ($uid === false) {
                    $uid = $this->uid;
                }

                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($uid);
                }

                $_projects = [];

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
                            $_projects[$group["acronym"]] = max(@(int)$projects[$group["acronym"]], (int)$group["level"]);
                        } else {
                            $_projects[$group["acronym"]] = (int)$group["level"];
                        }
                    }
                }

                $levels = $this->db->get("select acronym, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where uid = {$uid} and uid > 0 order by level", false, [
                    "level" => "level",
                    "acronym" => "acronym",
                ]);

                foreach ($levels as $level) {
                    if (@(int)$projects[$level["acronym"]]) {
                        if ((int)$level["level"] > 0) {
                            $_projects[$level["acronym"]] = min(@(int)$projects[$level["acronym"]], (int)$level["level"]);
                        } else {
                            unset($projects[$level["acronym"]]);
                        }
                    } else {
                        if ((int)$level["level"] > 0) {
                            $_projects[$level["acronym"]] = (int)$level["level"];
                        }
                    }
                }

                $this->cacheSet($key, $_projects);
                return $_projects;
            }

            /**
             * @inheritDoc
             */
            public function myGroups($returnGids = false)
            {
                $groups = loadBackend("groups");

                $g = [];

                if ($groups) {
                    $groups = $groups->getGroups($this->uid);

                    if ($returnGids) {
                        foreach ($groups as $group) {
                            $g[] = $group["gid"];
                        }
                    } else {
                        foreach ($groups as $group) {
                            $g[] = $group["acronym"];
                        }
                    }
                }

                return $g;
            }

            /**
             * @inheritDoc
             */
            public function myPrimaryGroup($returnGids = false)
            {
                $groups = loadBackend("groups");
                $users = loadBackend("users");

                $g = null;

                if ($groups && $users) {
                    $user = $users->getUser($this->uid);

                    if ($user) {
                        if ($returnGids) {
                            $g = (int)$user["primaryGroup"];
                        } else {
                            $g = $user["primaryGroupAcronym"];
                        }
                    }
                }

                return $g;
            }

            /**
             * @inheritDoc
             */
            public function getProjectViewers($projectId) {
                $key = $projectId?"VIEWERS:$projectId":"VIEWERS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if (!checkInt($projectId)) {
                    return false;
                }

                $_viewers = $this->db->get("select field, name from tt_projects_viewers where project_id = $projectId order by name");

                $this->cacheSet($key, $_viewers);
                return $_viewers;
            }

            /**
             * @inheritDoc
             */

            public function setProjectViewers($projectId, $viewers) {
                $this->clearCache();

                if (!checkInt($projectId)) {
                    return false;
                }

                $n = $this->db->modify("delete from tt_projects_viewers where project_id = $projectId");

                foreach ($viewers as $viewer) {
                    $n += $this->db->insert("insert into tt_projects_viewers (project_id, field, name) values (:project_id, :field, :name)", [
                        "project_id" => $projectId,
                        "field" => $viewer["field"],
                        "name" => $viewer["name"],
                    ], [ "silent" ]);
                }

                return $n;
            }

            /**
             * @inheritDoc
             */

            public function setProjectComments($projectId, $comments) {
                $this->clearCache();

                if (!checkInt($projectId)) {
                    return false;
                }

                $t = [];

                $comments = explode("\n", $comments);

                if (function_exists("mb_trim")) {
                    foreach ($comments as $c) {
                        if (mb_trim(preg_replace('~^\s+|\s+$~u', '', $c))) {
                            $t[] = $c;
                        }
                    }
                } else {
                    foreach ($comments as $c) {
                        if (trim(preg_replace('~^\s+|\s+$~u', '', $c))) {
                            $t[] = $c;
                        }
                    }
                }

                $comments = array_unique($t);

                if (extension_loaded('intl') === true) {
                    collator_asort(collator_create('root'), $comments);
                } else {
                    asort($comments);
                }

                $comments = trim(implode("\n", $comments));

                return $this->db->modify("update tt_projects set comments = :comments where project_id = :project_id", [
                    "project_id" => $projectId,
                    "comments" => $comments,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function getFavoriteFilters() {
                return $this->db->get("select filter, project, left_side, icon, color from tt_favorite_filters where login = :login", [
                    "login" => $this->login,
                ], [
                    "filter" => "filter",
                    "project" => "project",
                    "left_side" => "leftSide",
                    "icon" => "icon",
                    "color" => "color",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function addFavoriteFilter($filter, $project, $leftSide, $icon, $color) {
                if (!checkStr($filter)) {
                    return false;
                }

                if (!checkInt($leftSide)) {
                    return false;
                }

                return $this->db->insert("insert into tt_favorite_filters (login, filter, project, left_side, icon, color) values (:login, :filter, :project, :left_side, :icon, :color)", [
                    "login" => $this->login,
                    "filter" => $filter,
                    "project" => $project,
                    "left_side" => $leftSide,
                    "icon" => $icon,
                    "color" => $color,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteFavoriteFilter($filter, $all = false) {
                if (!checkStr($filter)) {
                    return false;
                }

                if ($all) {
                    return $this->db->modify("delete from tt_favorite_filters where filter = :filter", [
                        "filter" => $filter,
                    ]);
                } else {
                    return $this->db->modify("delete from tt_favorite_filters where login = :login and filter = :filter", [
                        "login" => $this->login,
                        "filter" => $filter,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */

            public function getCrontabs() {
                $cache = $this->cacheGet("CRONTABS");
                if ($cache) {
                    return $cache;
                }

                $_crontabs = $this->db->get("select * from tt_crontabs order by crontab, filter, action", false, [
                    "crontab_id" => "crontabId",
                    "crontab" => "crontab",
                    "project_id" => "projectId",
                    "filter" => "filter",
                    "uid" => "uid",
                    "action" => "action",
                ]);

                $this->cacheSet("CRONTABS", $_crontabs);
                return $_crontabs;
            }

            /**
             * @inheritDoc
             */
            public function addCrontab($crontab, $projectId, $filter, $uid, $action) {
                $this->clearCache();

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
                $this->clearCache();

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
                $this->db->modify("delete from tt_projects_custom_fields_nojournal where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_viewers where project_id not in (select project_id from tt_projects)");

                return parent::cleanup();
            }
        }
    }
