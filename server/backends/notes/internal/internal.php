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
            public function getNotes()
            {
                return $notes = $this->db->get("select * from notes where owner = :owner order by position_order", [
                    "owner" => $this->login,
                ], [
                    "note_id" => "id",
                    "note_subject" => "subject",
                    "note_body" => "body",
                    "checks" => "checks",
                    "category" => "category",
                    "remind" => "remind",
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
            public function addNote($subject, $body, $checks, $category, $remind, $icon, $font, $color, $x, $y, $z)
            {
                if (!checkStr($body)) {
                    setLastError("invalidParams");
                    return false;
                }

                $id = $this->db->insert("insert into notes (create_date, owner, note_subject, note_body, checks, category, remind, icon, font, color, position_left, position_top, position_order) values (:create_date, :owner, :note_subject, :note_body, :checks, :category, :remind, :icon, :font, :color, :position_left, :position_top, :position_order)", [
                    "create_date" => time(),
                    "owner" => $this->login,
                    "note_subject" => $subject ? : null,
                    "note_body" => $body ? : null,
                    "checks" => $checks ? 1 : 0,
                    "category" => $category ? : null,
                    "remind" => $remind ? : null,
                    "icon" => $icon ? : null,
                    "font" => $font ? : null,
                    "color" => $color ? : null,
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
            public function modifyNote11($id, $subject, $body, $category, $remind, $icon, $font, $color, $x, $y, $z)
            {
                if (!checkStr($body) || !checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update notes set note_subject = :note_subject, note_body = :note_body, category = :category, remind = :remind, icon = :icon, font = :font, color = :color, position_left = :position_left, position_top = :position_top, position_order = :position_order where note_id = :note_id and owner = :owner", [
                    "note_id" => $id,
                    "owner" => $this->login,
                    "note_subject" => $subject ? : null,
                    "note_body" => $body ? : null,
                    "category" => $category ? : null,
                    "remind" => $remind ? : null,
                    "icon" => $icon ? : null,
                    "font" => $font ? : null,
                    "color" => $color ? : null,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyNote4($id, $x, $y, $z)
            {
                if (!checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                error_log(print_r([
                    "note_id" => $id,
                    "owner" => $this->login,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
                ], true));

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
            public function deleteNote($id)
            {
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
                    if (count($arguments) == 11) {
                       return call_user_func_array([ $this, 'modifyNote11' ], $arguments);
                    }
                    else
                    if (count($arguments) == 4) {
                       return call_user_func_array([ $this, 'modifyNote4' ], $arguments);
                    }
                }
             }
        }
    }
