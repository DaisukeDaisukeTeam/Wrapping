<?php

namespace xtakumatutix\wrapping;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\Listener;
use pocketmine\event\Player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Binary;

Class Main extends PluginBase implements Listener {

    public function onEnable() 
    {
        $this->getLogger()->notice("読み込み完了_ver.1.0.0");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($sender instanceof Player) {
            if ($sender->getInventory()->all(Item::get(Item::PAPER))) {
                $handitem = $sender->getInventory()->getItemInHand();
                $id = $handitem->getID();
                $damage = $handitem->getDamage();
                $count = $handitem->getCount();
                $tag = $handitem->getNamedTag();

                if ($id === 0) {
                    $sender->sendMessage("§c >> 空気のラッピングを行うことは出来ません。");
                    return true;
                }

                if ($tag->offsetExists("wrapping")) {
                    $sender->sendMessage("§c >> 2重ラッピングを行うことは出来ません。");
                    return true;
                }

                $sender->getInventory()->removeItem(Item::get($id, $damage, $count));
                $sender->getInventory()->removeItem(Item::get(339, 0, 1));

                $name = $sender->getName();
                $item = Item::get(378, 0);
                $item->setLore(["中身はなにかな...?"]);
                $item->setCustomName("{$name}様より");
                $tag = $item->getNamedTag() ?? new CompoundTag('', []);
                $tag->setTag(new IntTag("wrapping", 1), true);
                $tag->setTag(new StringTag("wrapping1", self::itemSerialize($handitem)), true);
                $tag->setTag(new StringTag("wrapping2", $name), true);
                $item->setNamedTag($tag);
                $sender->getInventory()->addItem($item);
                $sender->sendMessage("§a >> ラッピングしました！！");

                return false;
            } else {
                $sender->sendMessage("§c >> 紙がありません");
                return true;
            }
        } else {
            $sender->sendMessage("ゲーム内で使用してください");
            return true;
        }
    }

    public function tap(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        $itemid = $item->getID();
        if ($itemid===378) {
            $tag = $item->getNamedTag();
            if ($tag->offsetExists("wrapping")) {
                $wrappingitem = self::itemDeserialize($tag->getString('wrapping1'));
                $name = $tag->getString('wrapping2');

                $player->getInventory()->removeItem((clone $item)->setCount(1));
                $player->getInventory()->addItem($wrappingitem);
                $player->sendMessage("§a >> {$name}様からのプレゼントです！");

                $pk = new PlaySoundPacket();
                $pk->soundName = 'random.levelup';
                $pk->x = $player->x;
                $pk->y = $player->y;
                $pk->z = $player->z;
                $pk->volume = 1;
                $pk->pitch = 1;
                $player->dataPacket($pk);
            }
        }
    }

    public static function itemSerialize(Item $item): string
    {
        $nbt = base64_encode($item->getCompoundTag());
        var_dump(strlen($nbt));
        return Binary::writeShort($item->getId()).Binary::writeByte($item->getDamage()).Binary::writeInt($item->getCount()).Binary::writeShort(strlen($nbt)).$nbt;//9‬b+?b
    }

    public static function itemDeserialize(String $str): Item
    {
        $binaryStream = new BinaryStream($str);
        return Item::get($binaryStream->getShort(),$binaryStream->getByte(),$binaryStream->getInt(),base64_decode($binaryStream->get($binaryStream->getShort())));
    }
}