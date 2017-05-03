<?php

namespace PiggyCustomEnchants;

use PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use pocketmine\entity\Arrow;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\Explosion;
use pocketmine\Player;

/**
 * Class EventListener
 * @package PiggyCustomEnchants
 */
class EventListener implements Listener
{
    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $this->checkToolEnchants($player, $event);
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $damager = $event->getDamager();
            $child = $event->getChild();
            if ($damager instanceof Player && $child instanceof Arrow) {
                $this->checkGlobalEnchants($damager, $entity, $event);
                $this->checkBowEnchants($damager, $entity, $event);
            }
        }
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof Player) {
                if ($damager->getInventory()->getItemInHand()->getId() == Item::BOW) { //TODO: Move to canUse() function
                    return false;
                }
                $this->checkGlobalEnchants($damager, $entity, $event);
                return true;
            }
        }
    }

    public function onShoot(EntitySpawnEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Arrow && $entity->shootingEntity instanceof Player) {
            $this->checkBowEnchants($entity->shootingEntity, $entity, $event);
        }
    }

    /**
     * @param Player $damager
     * @param Entity $entity
     * @param EntityDamageEvent $event
     */
    public function checkGlobalEnchants(Player $damager, Entity $entity, EntityEvent $event)
    {
        //TODO: Check to make sure you can use enchant with item
        if ($event instanceof EntityDamageEvent) {
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::LIFESTEAL);
            if ($enchantment !== null) {
                if ($damager->getHealth() + 2 + $enchantment->getLevel() <= $damager->getMaxHealth()) {
                    $damager->setHealth($damager->getHealth() + 2 + $enchantment->getLevel());
                } else {
                    $damager->setHealth($damager->getMaxHealth());
                }
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::BLIND);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::BLINDNESS);
                $effect->setAmplifier(0);
                $effect->setDuration(100 + 20 * $enchantment->getLevel());
                $effect->setVisible(false);
                $entity->addEffect($effect);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::DEATHBRINGER);
            if ($enchantment !== null) {
                $damage = 2 + ($enchantment->getLevel() / 10);
                $event->setDamage($event->getDamage() + $damage);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::POISON);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::POISON);
                $effect->setAmplifier($enchantment->getLevel());
                $effect->setDuration(60 * $enchantment->getLevel());
                $effect->setVisible(false);
                $entity->addEffect($effect);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::CRIPPLINGSTRIKE);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::NAUSEA);
                $effect->setAmplifier(0);
                $effect->setDuration(100 * $enchantment->getLevel());
                $effect->setVisible(false);
                $entity->addEffect($effect);
                $effect = Effect::getEffect(Effect::SLOWNESS);
                $effect->setAmplifier($enchantment->getLevel());
                $effect->setDuration(100 * $enchantment->getLevel());
                $effect->setVisible(false);
                $entity->addEffect($effect);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::VAMPIRE);
            if ($enchantment !== null) {
                if (isset($this->plugin->vampirecd[strtolower($damager->getName())]) && time() > $this->plugin->vampirecd[strtolower($damager->getName())] + 10) {
                    return false;
                }
                $this->plugin->vampirecd[strtolower($damager->getName())] = time();
                if ($damager->getHealth() + ($event->getDamage() / 2) <= $damager->getMaxHealth()) {
                    $damager->setHealth($damager->getHealth() + ($event->getDamage() / 2));
                } else {
                    $damager->setHealth($damager->getMaxHealth());
                }
                if ($damager->getFood() + ($event->getDamage() / 2) <= $damager->getMaxFood()) {
                    $damager->setFood($damager->getFood() + ($event->getDamage() / 2));
                } else {
                    $damager->setFood($damager->getMaxFood());
                }
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::CHARGE);
            if ($enchantment !== null) {
                if ($damager->isSprinting()) {
                    $event->setDamage($event->getDamage() * (1 + 0.10 * $enchantment->getLevel()));
                }
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::WITHER);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::WITHER);
                $effect->setAmplifier($enchantment->getLevel());
                $effect->setDuration(60 * $enchantment->getLevel());
                $effect->setVisible(false);
                $entity->addEffect($effect);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::DISARMING);
            if ($enchantment !== null) {
                if ($entity instanceof Player) {
                    $item = $entity->getInventory()->getItemInHand();
                    $entity->getInventory()->removeItem($item);
                    $motion = $entity->getDirectionVector()->multiply(0.4);
                    $entity->getLevel()->dropItem($entity->add(0, 1.3, 0), $item, $motion, 40);
                }
            }

        }
    }

    /**
     * @param Player $player
     * @param BlockBreakEvent $event
     */
    public function checkToolEnchants(Player $player, BlockEvent $event)
    {
        if ($event instanceof BlockBreakEvent) {
            $block = $event->getBlock();
            $drops = $event->getDrops();
            $enchantment = $this->plugin->getEnchantment($player->getInventory()->getItemInHand(), CustomEnchants::EXPLOSIVE);
            if ($enchantment !== null) {
                $explosion = new Explosion($block, $enchantment->getLevel() * 5, $player);
                $explosion->explodeA();
                $explosion->explodeB();
            }
            $enchantment = $this->plugin->getEnchantment($player->getInventory()->getItemInHand(), CustomEnchants::SMELTING);
            if ($enchantment !== null) {
                $finaldrop = array();
                $otherdrops = array();
                foreach ($drops as $drop) {
                    switch ($drop->getId()) {
                        case Item::COBBLESTONE:
                            array_push($finaldrop, Item::get(Item::STONE, 0, $drop->getCount()));
                            break;
                        case Item::IRON_ORE:
                            array_push($finaldrop, Item::get(Item::IRON_INGOT, 0, $drop->getCount()));
                            break;
                        case Item::GOLD_ORE:
                            array_push($finaldrop, Item::get(Item::GOLD_INGOT, 0, $drop->getCount()));
                            break;
                        case Item::SAND:
                            array_push($finaldrop, Item::get(Item::GLASS, 0, $drop->getCount()));
                            break;
                        case Item::CLAY:
                            array_push($finaldrop, Item::get(Item::BRICK, 0, $drop->getCount()));
                            break;
                        case Item::NETHERRACK:
                            array_push($finaldrop, Item::get(Item::NETHER_BRICK, 0, $drop->getCount()));
                            break;
                        case Item::STONE_BRICK: //SINCE WHEN CAN YOU SMELT STONE BRICKS TO MAKE THEM CRACKED???
                            if ($drop->getDamage() == 0) {
                                array_push($finaldrop, Item::get(Item::STONE_BRICK, 2, $drop->getCount()));
                            }
                            break;
                        case Item::CACTUS:
                            array_push($finaldrop, Item::get(Item::DYE, 2, $drop->getCount()));
                            break;
                        case Item::WOOD:
                        case Item::WOOD2:
                            array_push($finaldrop, Item::get(Item::COAL, 1, $drop->getCount()));
                            break;
                        case Item::SPONGE:
                            if ($drop->getDamage() == 1) {
                                array_push($finaldrop, Item::get(Item::SPONGE, 0, $drop->getCount()));
                            }
                            break;
                        default:
                            array_push($otherdrops, $drop);
                            break;
                    }
                }
                $event->setDrops(array_merge($finaldrop, $otherdrops));
            }
            $enchantment = $this->plugin->getEnchantment($player->getInventory()->getItemInHand(), CustomEnchants::ENERGIZING);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::HASTE);
                $effect->setAmplifier(1 + $enchantment->getLevel() - 2);
                $effect->setDuration(20);
                $effect->setVisible(false);
                $player->addEffect($effect);
            }
            $enchantment = $this->plugin->getEnchantment($player->getInventory()->getItemInHand(), CustomEnchants::QUICKENING);
            if ($enchantment !== null) {
                $effect = Effect::getEffect(Effect::SPEED);
                $effect->setAmplifier(3 + $enchantment->getLevel() - 2);
                $effect->setDuration(40);
                $effect->setVisible(false);
                $player->addEffect($effect);
            }
        }
    }

    /**
     * @param Player $damager
     * @param Entity $entity
     * @param EntityDamageEvent $event
     */
    public function checkBowEnchants(Player $damager, Entity $entity, EntityEvent $event)
    {
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::SHUFFLE);
            if ($enchantment !== null) {
                $pos1 = clone $damager->getPosition();
                $pos2 = clone $entity->getPosition();
                $damager->teleport($pos2);
                $entity->teleport($pos1);
            }
            $enchantment = $this->plugin->getEnchantment($damager->getInventory()->getItemInHand(), CustomEnchants::HEALING);
            if ($enchantment !== null) {
                if ($entity->getHealth() + $event->getDamage() + $enchantment->getLevel() <= $entity->getMaxHealth()) {
                    $entity->setHealth($entity->getHealth() + $event->getDamage() + $enchantment->getLevel());
                } else {
                    $entity->setHealth($entity->getMaxHealth());
                }
                $event->setDamage(0);
            }
        }
    }
}