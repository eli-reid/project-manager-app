<?php

namespace App\Core\Auth\Permission\Contracts;

interface PermissionRegistryContract
{
    /**
     * @param  array<int, array{
     *     resource:string,
     *     action:string,
     *     label?:string,
     *     description?:string,
     *     built_in_roles?:array<int, string>
     * }>  $definitions
     */
    public function registerPermissions(array $definitions): void;

    /**
     * @param  array<int, string>  $permissionKeys
     */
    public function grantToBuiltInRole(string $roleName, array $permissionKeys): void;

    /**
     * @return array<int, array{resource:string, action:string, label:string, description:string}>
     */
    public function permissions(): array;

    /**
     * @return array<string, array<int, string>>
     */
    public function builtInRolePermissions(): array;
}
