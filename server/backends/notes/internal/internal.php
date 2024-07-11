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

                if (!checkInt($x) || !checkInt($y) || !checkInt($z)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->insert("insert into notes (owner, note_subject, note_body, checks, category, remind, icon, font, color, position_left, position_top, position_order) values (:owner, :note_subject, :note_body, :checks, :category, :remind, :icon, :font, :color, :position_left, :position_top, :position_order)", [
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
            }

            /**
             * @inheritDoc
             */
            public function modifyNote11($id, $subject, $body, $category, $remind, $icon, $font, $color, $x, $y, $z)
            {
                if (!checkStr($body)) {
                    setLastError("invalidParams");
                    return false;
                }

                if (!checkInt($id) || !checkInt($x) || !checkInt($y) || !checkInt($z)) {
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
                if (!checkInt($id) || !checkInt($x) || !checkInt($y) || !checkInt($z)) {
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
            public function deleteNote($id)
            {
                if (!checkInt($id)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("delete from notes where note_id = :note_id and owner = :owner", [
                    "note_id" => $id,
                    "owner" => $this->login,
                    "position_left" => $x,
                    "position_top" => $y,
                    "position_order" => $z,
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
