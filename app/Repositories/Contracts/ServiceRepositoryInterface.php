<?php

namespace App\Repositories\Contracts;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

interface ServiceRepositoryInterface
{
    public function getAll();
    public function create(Request $request);
    public function findById(int $id);
    public function update(int $id, Request $request);
    public function delete(int $id);
    public function purchase(Service $service, User $user);
}
