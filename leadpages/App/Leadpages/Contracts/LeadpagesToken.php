<?php

namespace LeadpagesWP\Leadpages\Contracts;

/**
 * Abstract class to contract the names of functions to store and retrieve the Leadpages Token form the data store
 * Class LeadpagesToken
 *
 * @package LeadpagesWP\Leadpages\Contracts
 */
interface LeadpagesToken
{

    /**
     * Store token in database
     *
     * @return mixed
     */
    public function storeToken();

    /**
     * Get token from datastore
     *
     * @return mixed
     */
    public function getToken();

    /**
     * Remove token from database
     *
     * @return mixed
     */
    public function deleteToken();

    /**
     * Check if token is empty
     *
     * @return mixed
     */
    public function checkIfTokenIsEmpty();
}
