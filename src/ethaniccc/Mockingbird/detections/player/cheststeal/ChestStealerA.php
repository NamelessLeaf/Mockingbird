<?php

namespace ethaniccc\Mockingbird\detections\player\cheststeal;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\event\Event;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ChestStealerA extends Detection{

    public $transactions = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($this->transactions > (int) $this->getSetting("max_transactions")){
                $this->fail($user, "transactions={$this->transactions}");
            } else {
                if($this->transactions !== 0){
                    $this->reward($user, 0.99);
                }
            }
            $this->transactions = 0;
        }
    }

    public function handleEvent(Event $event, User $user): void{
        if($event instanceof InventoryTransactionEvent){
            foreach($event->getTransaction()->getInventories() as $inventory){
                if($inventory instanceof ChestInventory){
                    $this->transactions++;
                    return;
                }
            }
        }
    }

}