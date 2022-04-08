<?php
namespace App\Tests;

use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Entity\Room;
use Codeception\Actor;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends Actor
{
    use _generated\ApiTesterActions;

    public function havePortal(string $title, AuthSource $authSource = null): Portal
    {
        $authSource = $authSource ?: new AuthSourceLocal();
        $this->haveInRepository($authSource, [
            'title' => 'Lokal',
            'enabled' => true,
            'default' => true,
            'createRoom' => true,
        ]);

        $portal = new Portal();
        $portal->addAuthSource($authSource);
        $this->haveInRepository($portal, [
            'title' => $title,
            'status' => 1,
        ]);

        return $portal;
    }

    public function haveRoom(string $title, Portal $portal): Room
    {
        $room = new Room();
        $this->haveInRepository($room, [
            'contextId' => $portal->getId(),
            'creator_id' => 99,
            'modifier_id' => 99,
            'title' => $title,
            'status' => 1,
        ]);

        return $room;
    }

    public function amFullAuthenticated()
    {
        $this->sendPostAsJson('/login_check', [
            'username' => 'api_write',
            'password' => 'apiwrite',
        ]);
        $token = $this->grabDataFromResponseByJsonPath('$.token')[0];
        $this->amBearerAuthenticated($token);
    }

    public function amReadOnlyAuthenticated()
    {
        $this->sendPostAsJson('/login_check', [
            'username' => 'api_read',
            'password' => 'apiread',
        ]);
        $token = $this->grabDataFromResponseByJsonPath('$.token')[0];
        $this->amBearerAuthenticated($token);
    }
}