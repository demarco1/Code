<?php
/**
* @version      1.0.0 22.08.2015
* @author       Aran Dunkley & Elisabeth Medeiros de Oliveira
* @package      Jshopping
* @copyright    Copyright (C) 2015 Ligmincha Brasil
* @license      GNU/GPL
*/
define('_JSHOP_PAGSEGURO_TESTMODE_DESCRIPTION', 'For testing PagSeguro accounts');
define('_JSHOP_PAGSEGURO_EMAIL', 'E-mail:');
define('_JSHOP_PAGSEGURO_EMAIL_DESCRIPTION', 'E-mail para sua conta PagSeguro.');
define('_JSHOP_PAGSEGURO_TOKEN', 'Token:');
define('_JSHOP_PAGSEGURO_TOKEN_DESCRIPTION', 'Token para sua conta PagSeguro.');
define('_JSHOP_PAGSEGURO_TESTTOKEN', 'Token de test:');
define('_JSHOP_PAGSEGURO_TESTTOKEN_DESCRIPTION', 'Token para sua conta de teste PagSeguro.');
define('_JSHOP_PAGSEGURO_TRANSACTION_END_DESCRIPTION', 'Selecione o estado final que se establece no pedido, se o ordem do PagSeguro se realizou correctamente.');
define('_JSHOP_PAGSEGURO_TRANSACTION_PENDING_DESCRIPTION', 'Selecione o estado final que se establece no pedido, se a transacção de pagamento não se completou.');
define('_JSHOP_PAGSEGURO_TRANSACTION_FAILED_DESCRIPTION', 'Selecione o estado final que se estabelece no pedido para as transacções falhadas do PagSeguro.');
define('_JSHOP_PAGSEGURO_STATUS_1', 'Aguardando pagamento: o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.');
define('_JSHOP_PAGSEGURO_STATUS_2', 'Em análise: o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.');
define('_JSHOP_PAGSEGURO_STATUS_3', 'Paga: a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento.');
define('_JSHOP_PAGSEGURO_STATUS_4', 'Disponível: a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.');
define('_JSHOP_PAGSEGURO_STATUS_5', 'Em disputa: o comprador, dentro do prazo de liberação da transação, abriu uma disputa.');
define('_JSHOP_PAGSEGURO_STATUS_6', 'Devolvida: o valor da transação foi devolvido para o comprador.');
define('_JSHOP_PAGSEGURO_STATUS_7', 'Cancelada: a transação foi cancelada sem ter sido finalizada.');
define('_JSHOP_PAGSEGURO_STATUS_8', 'Chargeback debitado: o valor da transação foi devolvido para o comprador.');
define('_JSHOP_PAGSEGURO_STATUS_9', 'Em contestação: o comprador abriu uma solicitação de chargeback junto à operadora do cartão de crédito.');
