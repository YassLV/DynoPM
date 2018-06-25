# DynoPM
<p align="center">
    <img src="icon.png" width="200px" height="200px">
</p>

Implementing Dyno for PocketMine-MP

## Description
Create a Network system for PocketMine easily without AsyncTask and MySQL ! 

## Version
* __DynoPM Version__: 1.0
* __For PocketMine-MP__: 3.0.0
* __Current Protocol__: 1
* __Config Version__: 1.0

## Where can i get latest .phar?

Download latest .phar here: [Github Release](https://github.com/MineBuilderFR/DynoPM/releases)

## Installation
> __DynoPM needs [Dyno](https://github.com/MineBuilderFR/Dyno) to work__ <br/>

Download latest .phar and moved it to the PocketMine plugins folder <br/>

## Plugin Example
* __DynoMail__ : [Demo plugin for send Mail to player](https://github.com/MineBuilderFR/DynoMail)
* __DynoNetworkCount__ : SOON

## Documentation

Documentation Page: [Wiki](https://github.com/MineBuilderFR/DynoPM/wiki)

## Code Example

> You can see more example code on the documentation! <br/>

### Connection to Dyno

```php
public function onEnable()
{
    DynoPM::getInstance()->addPluginSyncWithDynoDescription($this, $dynoDesc);
    if (($this->dyno = DynoPM::getInstance()->getDynoByDescription($dynoDesc)) === null) {
         $this->getServer()->getPluginManager()->disablePlugin($this);
         return;
    }
    $this->getServer()->getLogger()->info("Plugin Started !");
}
```
    
### Creation Base and Table and Put Keys

```php
    $pk = new inputPacket();
    $final = new inputPacketLib();
    $final = $final
        ->createBase("Base Test", [
           BaseOptionsInterface::ONLY_IF_BASE_NOT_EXIST
        ])
        ->createTable("Table Test", TableOptionsInterface::ONLY_IF_TABLE_NOT_EXIST)
        ->getTable("Table Test")
        ->putBool("Bool !", true)
        ->putString("String !", "This is a string")
        ->finalInput();
    $pk->input = $final;
    $this->dyno->sendDataPacket($pk);
```
## Configuration
### Plugin

> Configuration in config.yml

enabled: Enable DynoPM plugin or not

    dynos:
       - enabled: Enabled Dyno Client
       ip: IP Dyno
       port: Port Dyno
       description: Description of your dyno (Allows to identify the DynoPM)
       password: Dyno Password


## Frequently Asked Questions
### What plugins can I create with DynoPM?

With DynoPM you can create any kind of plugin using MySQL/YML, there is no limitation .

### Why use Dyno instead of MySQL for PocketMine?

You do not have to use only dyno, you can use Mysql and Dyno. Dyno even has a function that automatically transfers the data sent to Dyno on Mysql and guarantees no lag on your PocketMine server.

### How does Dyno not create Lags?

This means that you can send multiple Information Packet per second without having to wait more than 2 seconds for an Asynchronous response.

### Developing with Dyno is easy?

You can look at the source of the example plugins.
