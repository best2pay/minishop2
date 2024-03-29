<?php
define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/index.php';

$modx->getService('error','error.modError');

if ($modx->getDebug()) $modx->log(xPDO::LOG_LEVEL_DEBUG, '[miniShop2:Best2pay] Payment notification request: ' . print_r($_REQUEST, true));

/* @var miniShop2 $miniShop2 */
$miniShop2 = $modx->getService('minishop2','miniShop2',$modx->getOption('minishop2.core_path',null,$modx->getOption('core_path').'components/minishop2/').'model/minishop2/', array());
$miniShop2->loadCustomClasses('payment');

$response = '';
$context = '';
$params = array();

if (class_exists('Best2pay')) {
	/* @var msPaymentInterface|Best2pay $handler */
	$handler = new Best2pay($modx->newObject('msOrder'));

	if (!empty($_REQUEST['reference'])) {
		$order = $modx->getObject('msOrder', $_REQUEST['reference']);

		if (isset($order)) {
			$response = $handler->receive($order, $_REQUEST);
			$context = $order->get('context');
			$params['msorder'] = $order->get('id');
		} else
			$response = $handler->paymentError('Order not found', $_REQUEST);
	} else {
		$modx->log(xPDO::LOG_LEVEL_ERROR, '[miniShop2:Best2pay] Wrong orderId.');
	}
} else {
	$modx->log(xPDO::LOG_LEVEL_ERROR, '[miniShop2:Best2pay] could not load payment class "Best2pay".');
}

$success = $cancel = $modx->getOption('site_url');

if ($id = $modx->getOption('setting_ms2_payment_best2pay_success_id', null, 0)) {
	$success = $modx->makeUrl($id, $context, $params, 'full');
}

if ($id = $modx->getOption('setting_ms2_payment_best2pay_cancel_id', null, 0)) {
	$cancel = $modx->makeUrl($id, $context, $params, 'full');
}

if ($response){
	$redirect = $success;
} else {
	$redirect = $cancel;
}

$modx->sendRedirect($redirect);