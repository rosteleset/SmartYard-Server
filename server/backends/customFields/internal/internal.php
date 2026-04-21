<?php

    /**
     * backends customFields namespace
     */

    namespace backends\customFields {

        /**
         * internal custom_fields class
         */

        class internal extends customFields {

            /**
             * @inheritDoc
             */

            public function getValues($applyTo, $id) {
                if (!checkStr($applyTo) || !checkStr($id)) {
                    return false;
                }

                $cf = $this->db->get("select field, value from custom_fields_values where apply_to = :apply_to and id = :id", [
                    "apply_to" => $applyTo,
                    "id" => $id,
                ], [
                    "field" => "field",
                    "value" => "value",
                ]);

                $v = [];

                foreach ($cf as $c) {
                    $v[$c["field"]] = $c["value"];
                }

                return $v;
            }

            /**
             * @inheritDoc
             */

            public function modifyValues($applyTo, $id, $set, $mode = "replace") {
                if (!is_array($set)) {
                    return false;
                }

                if ($mode !== "replace" && $mode !== "patch") {
                    return false;
                }

                $new = [];

                foreach ($set as $f => $v) {
                    if (!checkStr($f) || !checkStr($v)) {
                        return false;
                    }
                    $new[$f] = $v;
                }

                $old = $this->getValues($applyTo, $id);

                if (!is_array($old)) {
                    return false;
                }

                foreach ($old as $of => $ov) {
                    foreach ($new as $nf => $nv) {
                        if ($of == $nf && $ov != $nv) {
                            if ($nv) {
                                if ($this->db->modify("update custom_fields_values set value = :value where apply_to = :apply_to and id = :id and field = :field", [
                                    "apply_to" => $applyTo,
                                    "id" => $id,
                                    "field" => $nf,
                                    "value" => $nv,
                                ]) === false) {
                                    return false;
                                }
                            } else {
                                if ($this->db->modify("delete from custom_fields_values where apply_to = :apply_to and id = :id and field = :field", [
                                    "apply_to" => $applyTo,
                                    "id" => $id,
                                    "field" => $nf,
                                ]) === false) {
                                    return false;
                                }
                            }
                        }
                    }
                }

                if ($mode === "replace") {
                    foreach ($old as $f => $v) {
                        if (!@$new[$f]) {
                            if ($this->db->modify("delete from custom_fields_values where apply_to = :apply_to and id = :id and field = :field", [
                                "apply_to" => $applyTo,
                                "id" => $id,
                                "field" => $f,
                            ]) === false) {
                                return false;
                            }
                        }
                    }
                }

                foreach ($new as $f => $v) {
                    if (!@$old[$f] && $v) {
                        if ($this->db->modify("insert into custom_fields_values (apply_to, id, field, value) values (:apply_to, :id, :field, :value)", [
                            "apply_to" => $applyTo,
                            "id" => $id,
                            "field" => $f,
                            "value" => $v,
                        ]) === false) {
                            return false;
                        }
                    }
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteValues($applyTo, $id) {
                return $this->db->modify("delete from custom_fields_values where apply_to = :apply_to and id = :id", [
                    "apply_to" => $applyTo,
                    "id" => $id,
                ]) !== false;
            }

            /**
             * @inheritDoc
             */

            public function searchByValue($applyTo, $field, $value) {
                return $this->db->get("select id from custom_fields_values where apply_to = :apply_to and field = :field and value = :value", [
                    "apply_to" => $applyTo,
                    "field" => $field,
                    "value" => $value,
                ], [
                    "id" => "id",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function getFields($applyTo) {
                if (!checkStr($applyTo)) {
                    return false;
                }

                $fields = $this->db->get("select * from custom_fields where apply_to = :apply_to order by weight", [
                    "apply_to" => $applyTo,
                ], [
                    "custom_field_id" => "customFieldId",
                    "apply_to" => "applyTo",
                    "catalog" => "catalog",
                    "type" => "type",
                    "field" => "field",
                    "type" => "type",
                    "field_display" => "fieldDisplay",
                    "field_description" => "fieldDescription",
                    "regex" => "regex",
                    "link" => "link",
                    "format" => "format",
                    "editor" => "editor",
                    "indx" => "indx",
                    "search" => "search",
                    "required" => "required",
                    "magic_class" => "magicClass",
                    "magic_function" => "magicFunction",
                    "magic_hint" => "magicHint",
                    "add" => "add",
                    "modify" => "modify",
                    "tab" => "tab",
                ]);

                if (!is_array($fields)) {
                    return [];
                }

                $customFieldIds = [];

                foreach ($fields as $field) {
                    $customFieldId = @$field["customFieldId"];

                    if (checkInt($customFieldId) && $customFieldId > 0) {
                        $customFieldIds[$customFieldId] = $customFieldId;
                    }
                }

                $optionsByCustomFieldId = [];

                if (count($customFieldIds)) {
                    $options = $this->db->get("select * from custom_fields_options where custom_field_id in (" . implode(", ", $customFieldIds) . ") order by custom_field_id, display_order, option", false, [
                        "custom_field_id" => "customFieldId",
                        "option" => "option",
                        "display_order" => "displayOrder",
                        "option_display" => "optionDisplay",
                    ]);

                    if (is_array($options)) {
                        foreach ($options as $option) {
                            $optionsByCustomFieldId[@$option["customFieldId"]][] = $option;
                        }
                    }
                }

                foreach ($fields as &$field) {
                    $field["options"] = @$optionsByCustomFieldId[@$field["customFieldId"]] ?: [];
                }
                unset($field);

                return $fields;
            }

            /**
             * @inheritDoc
             */

            public function cleanup() {
                $n = 0;

                $n += $this->db->modify("delete from custom_fields_values where apply_to not in (select apply_to from custom_fields)");
                $n += $this->db->modify("delete from custom_fields_values where field not in (select field from custom_fields)");
                $n += $this->db->modify("delete from custom_fields_options where custom_field_id not in (select custom_field_id from custom_fields)");

                return $n;
            }

            public function cron($part) {
                if ($part === "5min") {
                    $this->cleanup();
                }

                return true;
            }
        }
    }
