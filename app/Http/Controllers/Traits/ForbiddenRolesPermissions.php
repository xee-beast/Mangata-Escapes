<?php

 namespace App\Http\Controllers\Traits;

 trait ForbiddenRolesPermissions {
     /**
      * Get the roles that the current user cannot manipulate.
      *
      * @return array
      */
     private function forbiddenRoles() {
         $forbiddenRoles = ['super admin'];

         if(auth()->user()->isAdmin() && !auth()->user()->isSuper()) {
             array_push($forbiddenRoles, 'admin');
         }

         return $forbiddenRoles;
     }

     /**
      * Get the permissions that the current user cannot manipulate.
      *
      * @return array
      */
     private function forbiddenPermissions() {
         $forbiddenPermissions = ['manage roles', 'manage employee roles', 'manage employee permissions'];

         return $forbiddenPermissions;
     }
 }
