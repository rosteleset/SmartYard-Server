<?php

    /**
     * backends notes namespace
     */

    namespace backends\notes {

        /**
         * internal notes class
         */

        class internal extends notes {

            /**
             * @inheritDoc
             */

            public function getNotes() {
                return $notes = $this->db->get("select * from notes where owner = :owner order by position_order", [
                    "owner" => $this->login,
                ], [
                    "note_id" => "id",
                    "note_subject" => "subject",
                    "note_body" => "body",
                    "note_type" => "type",
                    "category" => "category",
                    "remind" => "remind",
                    "reminded" => "reminded",
                    "icon" => "icon",
                    "font" => "font",
                    "color" => "color",
                    "position_left" => "x",
                    "position_top" => "y",
                    "position_order" => "z",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function addNote($subject, $body, $type, $category, $remind, $icon, $font, $color, $x, $y, $z) {
                $body = trim($body);

                if (!$body) {
                    setLastError("invalidParams");
                    return false;
                }

                if (!checkStr($type, [ "variants" => [ "text", "markdown", "checks", ]])) {
                    setLastError("invalidParams");
                    return false;
                }

                $id = $this->db->insert("insert into notes (create_date, owner, note_subject, note_body, note_type, category, remind, reminded, icon, font, color, position_left, position_top, position_order) values (:create_date, :owner, :note_subject, :note_body, :checks, :category, :remind, :reminded, :icon, :font, :color, :position_left, :position_top, :position_order)", [
                    "create_date" => time(),
                    "owner" => $this->login,
                    "note_subject" => $subject ?: null,
                    "note_body" => $body ?: null,
                    "note_type" => $type,
                    "category" => $category ?: null,
                    "remind" => $remind ?: null,
                    "reminded" => ((int)$remind > time()) ? 0 : 1,
                    "icon" => $icon ?: null,
                    "font" => $font ?: null,
                    "color" => $color ?: null,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
                ]);

                return $notes = $this->db->get("select * from notes where owner = :owner and note_id = :note_id", [
                    "owner" => $this->login,
                    "note_id" => $id,
                ], [
                    "note_id" => "id",
                    "note_subject" => "subject",
                    "note_body" => "body",
                    "checks" => "checks",
                    "category" => "category",
                    "remind" => "remind",
                    "reminded" => "reminded",
                    "icon" => "icon",
                    "font" => "font",
                    "color" => "color",
                    "position_left" => "x",
                    "position_top" => "y",
                    "position_order" => "z",
                ], [
                    "singlify",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyNote12($id, $subject, $body, $type, $category, $remind, $icon, $font, $color, $x, $y, $z) {
                $body = trim($body);

                if (!$body || !checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                if (!checkStr($type, [ "variants" => [ "text", "markdown", "checks", ]])) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update notes set note_subject = :note_subject, note_body = :note_body, note_type = :note_type, category = :category, remind = :remind, reminded = :reminded, icon = :icon, font = :font, color = :color, position_left = :position_left, position_top = :position_top, position_order = :position_order where note_id = :note_id and owner = :owner", [
                    "note_id" => $id,
                    "owner" => $this->login,
                    "note_subject" => $subject ?: null,
                    "note_body" => $body ?: null,
                    "note_type" => $type,
                    "category" => $category ?: null,
                    "remind" => $remind ?: null,
                    "reminded" => ((int)$remind > time()) ? 0 : 1,
                    "icon" => $icon ?: null,
                    "font" => $font ?: null,
                    "color" => $color ?: null,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyNote4($id, $x, $y, $z) {
                if (!checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update notes set position_left = :position_left, position_top = :position_top, position_order = :position_order where note_id = :note_id and owner = :owner", [
                    "note_id" => $id,
                    "owner" => $this->login,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyNote3($id, $line, $checked) {
                if (!checkInt($id) || !checkInt($line) || !checkInt($checked)) {
                    setLastError("invalidParams");
                    return false;
                }

                $body = $this->db->get("select note_body from notes where note_id = :note_id and owner = :owner and checks = 1", [
                    "note_id" => $id,
                    "owner" => $this->login,
                ], [
                    "note_body" => "body",
                ], [
                    "fieldlify"
                ]);

                $body = explode("\n", $body);
                if (@$body[$line]) {
                    $body[$line][0] = $checked ? "+" : "-";
                }
                $body = implode("\n", $body);

                return $this->db->modify("update notes set note_body = :note_body where note_id = :note_id and owner = :owner and checks = 1", [
                    "note_id" => $id,
                    "owner" => $this->login,
                    "note_body" => $body,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteNote($id) {
                if (!checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("delete from notes where note_id = :note_id and owner = :owner", [
                    "note_id" => $id,
                    "owner" => $this->login,
                ]);
            }

            public function __call($method, $arguments) {
                if ($method == 'modifyNote') {
                    if (count($arguments) == 12) {
                        return call_user_func_array([ $this, 'modifyNote12' ], $arguments);
                    }
                    else
                    if (count($arguments) == 4) {
                        return call_user_func_array([ $this, 'modifyNote4' ], $arguments);
                    }
                    else
                    if (count($arguments) == 3) {
                        return call_user_func_array([ $this, 'modifyNote3' ], $arguments);
                    }
                }
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "minutely") {
                    $notes = $this->db->get("select note_id, owner, note_subject, note_body from notes where reminded = 0 and remind < :now", [
                        "now" => time(),
                    ], [
                        "note_id" => "id",
                        "owner" => "owner",
                        "note_subject" => "subject",
                        "note_body" => "body",
                    ]);

                    $users = loadBackend("users");

                    foreach ($notes as $note) {
                        $users->notify($users->getUidByLogin($note["owner"]), $note["subject"], $note["body"]);
                        $this->db->modify("update notes set reminded = 1 where note_id = {$note["id"]}");
                    }
                }

                return true;
            }
        }
    }
