<?php

namespace App;
/*
 * A trait to handle authorization based on users permissions for given controller
 */

trait Authorizable
{
    /**
     * Abilities
     *
     * @var array
     */
    private $abilities = [
        'card_list' => 'view',
        'car_in_site' => 'view',
        'entry_log_db' => 'view',
        'entry_log_reviewed' => 'view',
        'entry_history' => 'view',
        'lane_config' => 'view',
        'index' => 'view',
        'edit' => 'edit',
        'show' => 'view',
        'update' => 'edit',
        'create' => 'add',
        'store' => 'add',
        'destroy' => 'delete'
    ];

    /**
     * Override of callAction to perform the authorization before it calls the action
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function callAction($method, $parameters)
    {        
        if( $ability = $this->getAbility($method) ) {

            // echo auth()->user()->getAllPermissions();
            // echo '<br>';
            // echo $ability;
            
            $this->authorize($ability);
 
        }

        return parent::callAction($method, $parameters);
    }

    /**
     * Get ability
     *
     * @param $method
     * @return null|string
     */
    public function getAbility($method)
    {
        $routeName = explode('.', \Request::route()->getName());

        $action = array_get($this->getAbilities(), $method);
                
        // echo $action . '_' . $routeName[0].'<br>';

        return $action ? $action . '_' . $routeName[0] : null;
    }

    /**
     * @return array
     */
    private function getAbilities()
    {
        return $this->abilities;
    }

    /**
     * @param array $abilities
     */
    public function setAbilities($abilities)
    {
        $this->abilities = $abilities;
    }
}