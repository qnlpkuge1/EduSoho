<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140716110046 extends AbstractMigration
{
    public function up(Schema $schema)
    {
       $this->addSql("ALTER TABLE `user` ADD `varcharField6` VARCHAR(1024) NULL AFTER `varcharField5`, ADD `varcharField7` VARCHAR(1024) NULL AFTER `varcharField6`, ADD `varcharField8` VARCHAR(1024) NULL AFTER `varcharField7`, ADD `varcharField9` VARCHAR(1024) NULL AFTER `varcharField8`, ADD `varcharField10` VARCHAR(1024) NULL AFTER `varcharField9`;");

    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs

    }
}