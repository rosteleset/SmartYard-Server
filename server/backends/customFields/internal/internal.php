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

            public function modifyValues($applyTo, $id, $set) {
                $new = [];

                foreach ($set as $f => $v) {
                    if (!checkStr($f) || !checkStr($v)) {
                        return false;
                    }
                    $new[$f] = $v;
                }

                $old = $this->getValues($applyTo, $id);

                foreach ($old as $of => $ov) {
                    foreach ($set as $nf => $nv) {
                        if ($of == $nf && $ov != $nv) {
                            if ($nv) {
                                $this->db->modify("update custom_fields_values set value = :value where apply_to = :apply_to and id = :id and field = :field", [
                                    "apply_to" => $applyTo,
                                    "id" => $id,
                                    "field" => $nf,
                                    "value" => $nv,
                                ]);
                            } else {
                                $this->db->modify("delete from custom_fields_values where apply_to = :apply_to and id = :id and field = :field", [
                                    "apply_to" => $applyTo,
                                    "id" => $id,
                                    "field" => $nf,
                                ]);
                            }
                        }
                    }
                }

                foreach ($old as $f => $v) {
                    if (!@$new[$f]) {
                        $this->db->modify("delete from custom_fields_values where apply_to = :apply_to and id = :id and field = :field", [
                            "apply_to" => $applyTo,
                            "id" => $id,
                            "field" => $f,
                        ]);
                    }
                }

                foreach ($new as $f => $v) {
                    if (!@$old[$f] && $v) {
                        $this->db->modify("insert into custom_fields_values (apply_to, id, field, value) values (:apply_to, :id, :field, :value)", [
                            "apply_to" => $applyTo,
                            "id" => $id,
                            "field" => $f,
                            "value" => $v,
                        ]);
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

                return $this->db->get("select * from custom_fields where apply_to = :apply_to order by weight", [
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
