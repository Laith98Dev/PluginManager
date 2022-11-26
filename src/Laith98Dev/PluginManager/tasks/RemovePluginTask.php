<?php

namespace Laith98Dev\PluginManager\tasks;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (c) Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Gihhub: Laith98Dev
 *	Email: spt.laithdev@gamil.com
 *	Donate: https://paypal.me/Laith113
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use Laith98Dev\PluginManager\Main;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;
use Vecnavium\FormsUI\ModalForm;

class RemovePluginTask extends Task {

    public function __construct(
        private Main $loader,
        private Plugin $plugin,
        private ?Player $player = null
    ){
        // NOOP
    }

    public function getLoader(): Main
    {
        return $this->loader;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getPLayer(): ?Player
    {
        return $this->player;
    }

    public function onRun(): void
    {
        $loader = $this->getLoader();
        $plugin = $this->getPlugin();
        $player = $this->getPlayer();

        $reflection = new \ReflectionClass(PluginBase::class);
		$file = $reflection->getProperty("file");
		$file->setAccessible(true);
		$pharPath = str_replace("\\", "/", rtrim($file->getValue($plugin), "\\/"));
        $path = str_replace("phar://", "", $pharPath);

        Filesystem::recursiveUnlink($path);

        assert(!is_file($path) && !is_dir($path), "It must have been removed the plugin");

        $player?->sendMessage(TextFormat::YELLOW . "Plugin removed successfully!");

        $form = new ModalForm(fn (Player $player, $data = null) => match ($data)
        {
            true => (function () use ($player){
                $player->sendMessage(TextFormat::GREEN . "The server will restart after 5 seconds");
                $this->getLoader()->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $this->getLoader()->getServer()->shutdown()), 5 * 20);
            })(),
            default => null
        });

        $form->setTitle("Restart Form");

        $form->setContent("Plugin Removed Successfully!\n restart the server now?");

        $form->setButton1("Yes");
        $form->setButton2("Maybe later");

        $player?->sendForm($form);
    }
}