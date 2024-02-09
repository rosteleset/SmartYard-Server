<?php

/**
 * backends companies namespace
 */

namespace backends\companies
{

    use backends\backend;

    /**
     * base companies class
     */
    abstract class companies extends backend
    {
        /**
         * @return false|array
         */
        abstract public function getCompanies();

        /**
         * @return false|array
         */
        abstract public function getCompany($companyId);

        /**
         * @param $type
         * @param $uid
         * @param $name
         * @param $contacts
         * @param $comment
         * @return false|integer
         */
        abstract public function addCompany($type, $uid, $name, $contacts, $comment);

        /**
         * @param $companyId
         * @param $type
         * @param $uid
         * @param $name
         * @param $contacts
         * @param $comment
         * @return boolean
         */
        abstract public function modifyCompany($type, $uid, $companyId, $name, $contacts, $comment);

        /**
         * @param $companyId
         * @return boolean
         */
        abstract public function deleteCompany($companyId);
    }
}
