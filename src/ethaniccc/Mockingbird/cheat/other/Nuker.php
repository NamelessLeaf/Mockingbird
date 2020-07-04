<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;

class Nuker extends Cheat{

    private $lastBreak = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isCreative()) return;

        if(!isset($this->lastBreak[$name])){
            $this->lastBreak[$name] = microtime(true);
            return;
        }

        $timeDiff = microtime(true) - $this->lastBreak[$name];
        if($timeDiff < 0.006){
            if(!isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] = 0;
            }
            $this->suspicionLevel[$name] += 1;
            if($this->suspicionLevel[$name] >= 5){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                $this->suspicionLevel[$name] = 1;
            }
        } else {
            if(isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] *= 0.5;
            }
        }

        $this->lastBreak[$name] = microtime(true);
    }

}