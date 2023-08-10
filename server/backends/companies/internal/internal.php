<?php

/**
 * backends companies namespace
 */

namespace backends\companies
{

    /**
     * internal.db companies class
     */
    class internal extends companies
    {
        /**
         * @inheritDoc
         */
        public function getCompanies()
        {
            return $this->db->get("select * from companies order by name", false, [
                "company_id" => "companyId",
                "company_type" => "type",
                "name" => "name",
                "uid" => "uid",
                "contacts" => "contacts",
                "comment" => "comment"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function getCompany($companyId)
        {
            if (!checkInt($companyId)) {
                return false;
            }

            return $this->db->get("select * from companies where company_id = $companyId", false, [
                "company_id" => "companyId",
                "company_type" => "type",
                "name" => "name",
                "uid" => "uid",
                "contacts" => "contacts",
                "comment" => "comment"
            ], [
                "singlify"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function addCompany($name, $type, $uid, $contacts, $comment)
        {
            if (!trim($name) || !checkInt($type)) {
                return false;
            }

            return $this->db->insert("insert into companies (name, company_type, uid, contacts, comment) values (:name, :company_type, :uid, :contacts, :comment)", [
                "name" => $name,
                "company_type" => $type,
                "uid" => $uid,
                "contacts" => $contacts,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function modifyCompany($companyId, $name, $type, $uid, $contacts, $comment)
        {
            if (!checkInt($companyId) || !trim($name) || !checkInt($type)) {
                return false;
            }

            return $this->db->modify("update companies set name = :name, company_type = :type, uid = :uid, contacts = :contacts, comment = :comment where company_id = $companyId", [
                "name" => $name,
                "type" => $type,
                "uid" => $uid,
                "contacts" => $contacts,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function deleteCompany($companyId)
        {
            if (!checkInt($companyId)) {
                setLastError("noId");
                return false;
            }

            return $this->db->modify("delete from companies where company_id = $companyId");
        }
    }
}
