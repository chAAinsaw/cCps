<?php

namespace chainsaw\cps\listeners;

use chainsaw\cps\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;

class CPSListener implements Listener
{
    private const CPS_WINDOW = 1.0;
    private array $cps = [];
    private array $cpsEnabled = [];

    public function __construct(private Main $plugin) {}

    public function onJoin(PlayerJoinEvent $event): void {
        $name = $event->getPlayer()->getName();
        $this->cpsEnabled[$name] = $this->plugin->getPlayerSetting($name, "enabled", true);
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $name = $event->getPlayer()->getName();
        unset($this->cps[$name], $this->cpsEnabled[$name]);
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();

        if ($player === null) return;

        $shouldHandle = false;

        if ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEvent::ATTACK_NODAMAGE) {
            $shouldHandle = true;
        } elseif ($packet instanceof InventoryTransactionPacket && $packet->trData instanceof UseItemOnEntityTransactionData) {
            $shouldHandle = true;
        } elseif ($packet instanceof PlayerActionPacket && $packet->action === PlayerAction::START_BREAK) {
            $shouldHandle = true;
        }

        if ($shouldHandle) {
            $this->addCPS($player);

            if ($this->cpsEnabled[$player->getName()] ?? false) {
                $cps = $this->getCPS($player);
                $format = str_replace("{cps}", (string)$cps, $this->plugin->getConfig()->get("popup_format", "§f[§6CPS§f] §6{cps}"));
                $player->sendPopup($format);
            }
        }
    }

    private function addCPS(Player $player): void {
        $name = $player->getName();
        $time = microtime(true);

        if (isset($this->cps[$name])) {
            $this->cps[$name] = array_filter(
                $this->cps[$name],
                static fn(float $t): bool => ($time - $t) <= self::CPS_WINDOW
            );
        }

        $this->cps[$name][] = $time;
    }

    public function getCPS(Player $player): int {
        return count($this->cps[$player->getName()] ?? []);
    }

    public function setCPS(Player $player, bool $enabled): void {
        $name = $player->getName();
        $this->cpsEnabled[$name] = $enabled;
        $this->plugin->setPlayerSetting($name, "enabled", $enabled);
    }

    public function isCPSEnabled(Player $player): bool {
        return $this->cpsEnabled[$player->getName()] ?? false;
    }
}