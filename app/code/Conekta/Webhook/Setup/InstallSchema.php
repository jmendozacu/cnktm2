<?php

namespace Conekta\Webhook\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('conekta_info');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {

            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'payment_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'record id'
                )
                ->addColumn(
                    'order_reference_id',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => false],
                    'order_reference_id'
                )
                ->addColumn(
                    'card_name',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_name'
                )
                ->addColumn(
                    'card_exp_month',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_exp_month'
                )
                ->addColumn(
                    'card_exp_year',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_exp_year'
                )
                ->addColumn(
                    'card_auth_code',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_auth_code'
                )
                ->addColumn(
                    'card_object',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_object'
                )
                ->addColumn(
                    'card_last4',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_last4'
                )
                ->addColumn(
                    'card_brand',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_brand'
                )
                ->addColumn(
                    'card_token_id',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_token_id'
                )
                ->addColumn(
                    'card_token_object',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_token_object'
                )
                ->addColumn(
                    'card_token_used',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_token_used'
                )
                ->addColumn(
                    'card_token_livemode',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'card_token_livemode'
                )
                ->addColumn(
                    'oxxo_barcode',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_barcode'
                )
                ->addColumn(
                    'oxxo_barcode_url',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_barcode_url'
                )
                ->addColumn(
                    'oxxo_object',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_object'
                )
                ->addColumn(
                    'oxxo_type',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_type'
                )
                ->addColumn(
                    'oxxo_expires_at',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_expires_at'
                )
                ->addColumn(
                    'oxxo_store_name',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'oxxo_store_name'
                )
                ->addColumn(
                    'spei_clabe',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_clabe'
                )
                ->addColumn(
                    'spei_bank',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_bank'
                )
                ->addColumn(
                    'spei_issuing_account_holder',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_issuing_account_holder'
                )
                ->addColumn(
                    'spei_issuing_account_tax_id',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_issuing_account_tax_id'
                )
                ->addColumn(
                    'spei_issuing_account_bank',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_issuing_account_bank'
                )
                ->addColumn(
                    'spei_issuing_account_number',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_issuing_account_number'
                )
                ->addColumn(
                    'spei_receiving_account_holder',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_receiving_account_holder'
                )
                ->addColumn(
                    'spei_receiving_account_tax_id',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_receiving_account_tax_id'
                )
                ->addColumn(
                    'spei_receiving_account_number',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_receiving_account_number'
                )
                ->addColumn(
                    'spei_receiving_account_bank',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_receiving_account_bank'
                )
                ->addColumn(
                    'spei_reference_number',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_reference_number'
                )
                ->addColumn(
                    'spei_description',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_description'
                )
                ->addColumn(
                    'spei_tracking_code',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_tracking_code'
                )
                ->addColumn(
                    'spei_executed_at',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_executed_at'
                )
                ->addColumn(
                    'spei_object',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_object'
                )
                ->addColumn(
                    'spei_type',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_type'
                )
                ->addColumn(
                    'spei_expires_at',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => true],
                    'spei_expires_at'
                )
                ->setComment('Conekta payments info');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
} //hps_vault/Setup/InstallSchema.php