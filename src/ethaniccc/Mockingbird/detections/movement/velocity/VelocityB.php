<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class VelocityB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 15;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceMotion <= ($user->transactionLatency / 50) + 2 && $user->currentMotion !== null && $user->player->isAlive()){
                $forward = $packet->getMoveVecZ();
                $strafe = $packet->getMoveVecX();
                $motion = clone $user->currentMotion;
                // replication: https://github.com/eldariamc/client/blob/c01d23eb05ed83abb4fee00f9bf603b6bc3e2e27/src/main/java/net/minecraft/entity/EntityFlying.java#L30
                $f = pow($strafe, 2) + pow($forward, 2);
                if($f >= 9.999999747378752E-5){
                    $f = sqrt($f);
                    if($f < 1){
                        $f = 1;
                    }
                    $friction = $user->onGround ? 0.16277136 / pow(($user->blockBelow !== null ? $user->blockBelow->getFrictionFactor() : 0.6), 3) : 0.02;
                    $f = $friction / $f;
                    $strafe *= $f;
                    $forward *= $f;
                    $f2 = sin($user->yaw * M_PI / 180);
                    $f3 = cos($user->yaw * M_PI / 180);
                    $motion->x += $strafe * $f3 - $forward * $f2;
                    $motion->z += $forward * $f3 + $strafe * $f2;
                }
                $motion->x *= 0.998;
                $motion->z *= 0.998;
                $expectedHorizontal = hypot($motion->x, $motion->z);
                if($expectedHorizontal < 0.2){
                    return;
                }
                $horizontalMove = hypot($user->moveDelta->x, $user->moveDelta->z);
                $percentage = $horizontalMove / $expectedHorizontal;
                $scaledPercentage = $percentage * 100;
                $maxPercentage = $this->getSetting("multiplier");
                if($user->timeSinceAttack < 2){
                    $maxPercentage *= 0.98;
                }
                if($percentage < $maxPercentage){
                    if(++$this->preVL > ($user->transactionLatency > 150 ? 40 : 30) && count($user->player->getBlocksAround()) === 0
                    && count($user->player->getLevel()->getCollisionBlocks(AABB::from($user)->expand(0.2, 0, 0.2))) === 0){
                        $keyList = count($user->pressedKeys) > 0 ? implode(", ", $user->pressedKeys) : "none";
                        $this->fail($user, "percentage=$scaledPercentage keys=$keyList");
                    }
                } else {
                    $this->preVL -= $this->preVL;
                    $this->reward($user, 0.995);
                }
            }
        }
    }

}