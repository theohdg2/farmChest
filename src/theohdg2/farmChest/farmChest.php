<?php

namespace theohdg2\farmChest;

use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\block\tile\Chest;
use pocketmine\block\TrappedChest;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class farmChest extends PluginBase implements Listener
{
    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
    }
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $player->getInventory()->getItemInHand();
        if($block instanceof TrappedChest){
            $rayon = $this->getConfig()->get("rayon",4);
            $auto_replant = $this->getConfig()->get("auto-replant",true);
            $message = $this->getConfig()->get("message",null);
            if($message !== null){
                $player->sendMessage($message);
            }

            $xmax = $block->getPosition()->add($rayon,0,0)->getX();
            $xmin = $block->getPosition()->add(-$rayon,0,0)->getX();

            $zmax = $block->getPosition()->add(0,0,$rayon)->getZ();
            $zmin = $block->getPosition()->add(0,0,-$rayon)->getZ();

            $ymax= $block->getPosition()->add(0,$rayon,0)->getY();
            $ymin = $block->getPosition()->add(0,-$rayon,0)->getY();

            $axis = new AxisAlignedBB((float)$xmin, (float)$ymin, (float)$zmin, (float)$xmax, (float)$ymax, (float)$zmax);
            $seedInChest = $this->getNearbyBlocks($axis,$player->getWorld(),$item,$auto_replant);
            $tile = $player->getWorld()->getTile($block->getPosition());
            if($tile instanceof Chest){
                foreach ($seedInChest as $array){
                    foreach ($array as $item) {
                        $tile->getInventory()->addItem($item);
                    }
                }
            }
        }
    }
    private function verify(Block $f,Item $item,int $depart = 0,bool $auto_replant = true): array{
        $seedInChest = [];
        if($f instanceof Crops){
            if($f->getAge() === 7) {
                foreach ($f->getDrops($item) as $drop){
                    $seedInChest[] = $drop;
                }
                if($auto_replant) {
                    $f->setAge(0);
                    $f->getPosition()->getWorld()->setBlock($f->getPosition(), $f);
                }else{
                    $f->getPosition()->getWorld()->setBlock($f->getPosition(),VanillaBlocks::AIR());
                }
            }
        }
        return $seedInChest;
    }

    public function getNearbyBlocks(AxisAlignedBB $bb,World $world,Item $item,bool $auto_replant) : array{
        $nearby = [];
        for ($y = $bb->minY;$y <= $bb->maxY;++$y) {
            for ($x = $bb->minX; $x <= $bb->maxX; ++$x) {
                for ($z = $bb->minZ; $z <= $bb->maxZ; ++$z) {
                    $nearby[] = $this->verify($world->getBlockAt($x,$y,$z),$item,count($nearby),$auto_replant);
                }
            }
        }
        return $nearby;
    }
}