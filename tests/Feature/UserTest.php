<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testRegisterSuccess()
    {
        $this->post('/api/users', [
           'username' => 'arya',
           'password' => 'rahasia',
           'name' => 'arya wardana'
        ])->assertStatus(201)->assertJson([
            "data" => [
                'username' => 'arya',
                'name' => 'arya wardana'
            ]
        ]);
    }

    public function testRegisterFailed()
    {
        $this->post('/api/users', [
            'username' => '',
            'password' => '',
            'name' => ''
         ])->assertStatus(400)->assertJson([
             "errors" => [
                'username' => [
                    "The username field is required."
                ],
                'name' => [
                    "The name field is required."
                ],
                'password' => [
                    "The password field is required."
                ]
             ]
         ]);
    }

    public function testRegisterFailedUsernameAlreadyExists()
    {
        $this->testRegisterSuccess();
        $this->post('/api/users', [
            'username' => 'arya',
            'password' => 'rahasia',
            'name' => 'arya wardana'
        ])->assertStatus(400)->assertJson([
            "errors" => [
                'username' => [
                    "username already registered"
                ],
            ]
        ]);
    }

    public function testLoginSuccess()
    {
        $this->seed([UserSeeder::class]);
        $this->post('/api/users/login', [
            'username' => 'test',
            'password' => 'test',
        ])->assertStatus(200)->assertJson([
            'data' => [
                'username' => 'test',
                'name' => 'test',
            ]
        ]);
        $user = User::where('username', 'test')->first();
        self::assertNotNull($user->token);
    }

    public function testLoginFailedUsernameNotFound()
    {
        $this->post('/api/users/login', [
            'username' => 'test',
            'password' => 'test',
        ])->assertStatus(401)->assertJson([
            'errors' => [
                'message' => [
                    'username or password is wrong'
                ]
            ]
        ]);
    }

    public function testLoginFailedPasswordWrong()
    {
        $this->seed([UserSeeder::class]);
        $this->post('/api/users/login', [
            'username' => 'test',
            'password' => 'salah',
        ])->assertStatus(401)->assertJson([
            'errors' => [
                'message' => [
                    'username or password is wrong'
                ]
            ]
        ]);
    }

    public function testGetSuccess()
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current', [
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson([
            'data' => [
                'username' => 'test',
                'name' => 'test',
            ]
        ]);
    }

    public function testGetUnauthorized()
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current')->assertStatus(401)->assertJson([
            'errors' => [
                'message' => [
                    'Unauthorized'
                ]
            ]
        ]);
    }

    public function testGetInvalidToken()
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current', [
            'Authorization' => 'salah'
        ])->assertStatus(401)->assertJson([
            'errors' => [
                'message' => [
                    'Unauthorized'
                ]
            ]
        ]);
    }

    public function testUpdatePasswordSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();
        $this->patch('/api/users/current',[
            'password' => 'baru',
        ], [
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson([
            'data' => [
                'username' => 'test',
                'name' => 'test',
            ]
        ]);
        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->password, $newUser->password);
    }
    public function testUpdateNameSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();
        $this->patch('/api/users/current',[
            'name' => 'arya',
        ], [
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson([
            'data' => [
                'username' => 'test',
                'name' => 'arya',
            ]
        ]);
        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->name, $newUser->name);
    }
    public function testUpdateFailedSuccess()
    {
        $this->seed([UserSeeder::class]);
        $this->patch('/api/users/current',[
            'name' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Maxime mollitia,
            molestiae quas vel sint commodi repudiandae consequuntur voluptatum laborum
            numquam blanditiis harum quisquam eius sed odit fugiat iusto fuga praesentium
            optio, eaque rerum! Provident similique accusantium nemo autem. Veritatis
            obcaecati tenetur iure eius earum ut molestias architecto voluptate aliquam
            nihil, eveniet aliquid culpa officia aut! Impedit sit sunt quaerat, odit,
            tenetur error, harum nesciunt ipsum debitis quas aliquid. Reprehenderit,',
        ], [
            'Authorization' => 'test'
        ])->assertStatus(400)->assertJson([
            'errors' => [
                'name' => [
                    'The name field must not be greater than 100 characters.'
                ],
            ]
        ]);
    }

    public function testLogoutSuccess()
    {
        $this->seed([UserSeeder::class]);
        $this->delete(uri:'/api/users/logout', headers:[
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson([
            "data" => true
        ]);

        $user = User::where('username', 'test')->first();
        self::assertNull($user->token);
    }

    public function testLogoutFailed()
    {
        $this->seed([UserSeeder::class]);
        $this->delete('/api/users/logout', [
            'Authorization' => 'salah'
        ])->assertStatus(401)->assertJson([
            "errors" => [
                "message" => [
                    "Unauthorized"
                ]
            ]
        ]);
    }
}
