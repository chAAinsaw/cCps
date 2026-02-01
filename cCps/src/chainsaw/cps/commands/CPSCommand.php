<?php

namespace chainsaw\cps\commands;

use chainsaw\cps\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\form\Form;

class CPSCommand extends Command implements PluginOwned
{
    public function __construct(private Main $plugin) {
        parent::__construct("cps", "Gérer l'affichage du CPS");
        $this->setPermission($this->plugin->getConfig()->get("permission", "vynox.cps"));
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Commande disponible uniquement en jeu !");
            return false;
        }

        $sender->sendForm($this->getCPSForm($sender));
        return true;
    }

    private function getCPSForm(Player $player): Form {
        $listener = $this->plugin->getCPSListener();
        $current = $listener->isCPSEnabled($player);

        return new class($listener, $player, $current, $this->plugin) implements Form {
            public function __construct(
                private $listener,
                private Player $player,
                private bool $current,
                private Main $plugin
            ) {}

            public function handleResponse(Player $player, $data): void {
                if ($data === null) return;

                $enabled = $data === 0;
                $this->listener->setCPS($player, $enabled);

                $config = $this->plugin->getConfig();
                $status = $enabled ? $config->get("status_enabled") : $config->get("status_disabled");
                $message = str_replace("{status}", $status, $config->get("toggle_message"));
                $player->sendMessage($message);
            }

            public function jsonSerialize(): array {
                $config = $this->plugin->getConfig();
                $statusEnabled = $config->get("status_enabled_display");
                $statusDisabled = $config->get("status_disabled_display");
                $status = $this->current ? $statusEnabled : $statusDisabled;

                return [
                    "type" => "form",
                    "title" => $config->get("form_title"),
                    "content" => str_replace("{status}", $status, $config->get("form_content")),
                    "buttons" => [
                        ["text" => $config->get("button_enabled")],
                        ["text" => $config->get("button_disabled")]
                    ]
                ];
            }
        };
    }

    public function getOwningPlugin(): Main {
        return $this->plugin;
    }
}