<?php

namespace App\Domains\Projects\Contracts;

use App\Domains\Projects\Models\Project;

interface ProjectTabPanelInterface
{
    /**
     * @param  array<string, array{modeParam:string,mode:string,detailParam:?string,detailId:string,isCreateMode:bool}>  $tabContext
     * @param  array<string, mixed>  $viewState
     * @return array{component:string,props:array<string, mixed>,key:string}|null
     */
    public function resolve(string $tabKey, Project $project, array $tabContext, array $viewState = []): ?array;
}
