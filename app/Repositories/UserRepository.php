<?php

namespace App\Repositories;

use Illuminate\Contracts\Foundation\Application;
use App\Models\User;

class UserRepository extends AbstractEloquentRepository
{
    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \App\Models\User $model
     */
    public function __construct(Application $app, User $model)
    {
        parent::__construct($app, $model);
    }

    /**
     * Dynamic copy `method-method` pada `Model` yang dituju,
     * tujuannya agar class ini tidak menambahkan secara
     * terus menerus `method-method` yang terdapat pada `Model`.
     *
     * method ini tidak perlu dipanggil dimanapun, karena otomatis
     * saat class ini terpanggil method jalan dengan sendiri-nya
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (is_callable([$this->model, $method])) {
            return call_user_func_array([$this->model, $method], $parameters);
        }

        return false;
    }
}