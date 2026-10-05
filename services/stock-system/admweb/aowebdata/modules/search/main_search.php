<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Table List ได้', 'redirect', 'SET');
$keyword            = REQ_get('qsearch', 'requset', 'str', '');
if (_AC_ == 'search') {
    if (empty($qsearch)) {
        CustomRedirectToUrl('index.php?module=' . _MODULE_ . '&mp=dashboard');
        exit;
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&qsearch=" . $qsearch . "&ac=searchview");
    exit;
}

if (_AC_ == 'searchview') {
    /*
    $aServiceList = DB_LIST_OR_TEST('billing_services', [
        'service_type' => ['!=' , 'NULL'],
        'OR' => [
            'service_name' => ['LIKE', "%$keyword%"]
        ]
    ], 0, $page, "ORDER BY is_active ASC, expire_date ASC ;");

    $aUserList = DB_LIST_OR_TEST('billing_user', [
        'member_id' => ['!=', ''],
        'OR' => [
            'firstname' => ['LIKE', "%$keyword%"],
            'lastname'  => ['LIKE', "%$keyword%"],
            'email'     => ['LIKE', "%$keyword%"],
            'company'   => ['LIKE', "%$keyword%"],
            'phone'   => ['LIKE', "%$keyword%"],
        ]
    ], 100, $page, "ORDER BY member_id DESC");

    $aOrderList = DB_ORDERLIST_SEARCH('billing_search_view', $keyword, 'master_order_id', 0, 1);
    $aInvoiceList = DB_ORDERLIST_SEARCH('billing_search_view', $keyword, 'invoice_id', 0, 1);
    */
}
?>
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">SEARCH [ <?php echo $qsearch; ?> ]</h1>
    </div>
</div>
<div id="page-content">
    <div class="row">
        <div class="col-md-12 pad-btm">
            <?php /* if ($aUserList['num_rows'] > 0) { ?>
                <div class="panel">
                    <div class="panel-body">
                        <div class="table-responsive">

                            <h2 class="text-main text-bold">MEMBER</h2>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">MEMID</th>
                                        <th>NAME</th>
                                        <th class="text-center">TAX</th>
                                        <th>COMPANY</th>
                                        <th>E-mail</th>
                                        <th class="text-center">PHONE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($aUserList['data'] as $k => $v) {
                                        echo '<tr>';
                                        echo '<td class="text-center">' . $v['member_id'] . '</td>';
                                        echo '<td><a href="' . URL_ADMIN . '/index.php?module=' . _MODULE_ . '&mp=user&ac=view&uid=' . $v['member_id'] . '" class="btn-link">' . $v['firstname'] . ' ' . $v['lastname'] . '</a></td>';
                                        echo '<td class="text-center">' . $v['namecard'] . '</td>';
                                        echo '<td><a href="' . URL_ADMIN . '/index.php?module=' . _MODULE_ . '&mp=user&ac=view&uid=' . $v['member_id'] . '" class="btn-link">' . $v['company'] . '</a></td>';
                                        echo '<td>' . $v['email'] . '</td>';
                                        echo '<td class="text-center">' . $v['phone'] . '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>


                        </div>
                    </div>
                </div>
            <?php }*/  ?>

            <?php
            /* if ($aServiceList['num_rows'] > 0) {  ?>
                <div class="panel">
                    <div class="panel-body">
                        <div class="table-responsive">

                            <h2 class="text-main text-bold">SERVICE</h2>
                            <table class="table table-striped table-vcenter">
							<thead>
								<tr>
									<th width="40" class="text-center">ID</th>
									<th width="150" class="text-right"></th>
									<th width="170" class="text-center">Status</th>
									<th width="70" text-center></th>
									<th>Service Name</th>
									<th width="160" class="text-center">หมดอายุ</th>
									<th width="160" class="text-center">กำหนดการ</th>
									<th width="90" class="text-right">Price</th>
									<th width="90" class="text-right">Price Next</th>
									<th width="80" class="text-right">ต้นทุน</th>
									<th width="110" class="text-right">กำไรสุทธิ</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$i = 0;
								$priceSum = 0;
								$price_next = 0;
								$costSum = 0;
								$totalSum = 0;
								foreach ($aServiceList['data'] as $k => $v) {
									$priceSum = $priceSum + $v['price'];
									$price_next = $price_next + $v['price_next'];
									$costSum = $costSum + $v['cost'];
									$bgc = 'default';
									$statusname = 'None';
									if ($v['is_active'] == 1) {
										$bgc = 'success';
										$statusname = 'Active';
									} elseif ($v['is_active'] == 2) {
										$bgc = 'info';
										$statusname = 'Pending';
									} elseif ($v['is_active'] == 3) {
										$bgc = 'danger';
										$statusname = 'Cancel';
									}
									$aView = DB_GET('billing_user', ['member_id' => $v['user_id']]);
									$userOrderUrl = _admin_buil_link('index.php?module=billing&mp=user_edit&id=' . $v['user_id'] . '&otb=userList', true);
									$deleteUrl = _admin_buil_link('index.php?module=billing&mp=service&ac=del&id=' . $v['service_id'] . '&type=' . $v['service_type']);
									$viewUrl = _admin_buil_link('index.php?module=billing&mp=service_view&id=' . $v['service_id'] . '&type=' . $v['service_type']);
									$diffdate = DifDate_billing($v['expire_date']);
									$pricediff = $v['price'] - $v['cost'];
									$totalSum = $totalSum + $pricediff;
								?>
									<tr>
										<td class="text-center"><?php echo $v['service_id']; ?></td>
										<td class="text-center">
											<select class="form-control" disabled>
												<option <?php echo ($v['is_active'] == '0') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=' . _MP_ . '&ac=changstatus&status=0&id=' . $v['service_id'] . '&type=' . $type); ?>">-</option>
												<option <?php echo ($v['is_active'] == '2') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=' . _MP_ . '&ac=changstatus&status=2&id=' . $v['service_id'] . '&type=' . $type); ?>">Pending</option>
												<option <?php echo ($v['is_active'] == '1') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=' . _MP_ . '&ac=changstatus&status=1&id=' . $v['service_id'] . '&type=' . $type); ?>">Active</option>
												<option <?php echo ($v['is_active'] == '3') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=' . _MP_ . '&ac=changstatus&status=3&id=' . $v['service_id'] . '&type=' . $type); ?>">Cancel</option>
											</select>
										</td>
										<td class="text-center">
											<div class="label label-table label-<?php echo $bgc; ?>"><?php echo $statusname; ?></div>
										</td>
										<?php if ($v['user_id'] != 1) { ?>
											<td><a href="<?php echo $userOrderUrl; ?>" class="btn btn-icon demo-pli-find-user icon-lg add-tooltip" data-original-title="<?php echo $aView['firstname'] . " " . $aView['lastname'] . " [" . $aView['company'] . "]" ?>" data-container="body"></a></td>
										<?php } else { ?>
											<td></td>
										<?php } ?>
										<td><a href="<?php echo $viewUrl; ?>" class="btn-link"><?php echo $v['service_name']; ?></a></td>
										<td class="text-center"><?php echo $diffdate; ?></td>
										<td class="text-center"><span class="text-muted"><i class="demo-pli-clock"></i> <?php echo $v['expire_date']; ?></span></td>
										<td class="text-right"><?php echo number_format($v['price'], 2); ?></td>
										<td class="text-right"><?php echo number_format($v['price_next'], 2); ?></td>
										<td class="text-right"><?php echo ($v['cost'] > 0) ? '<span class="text-danger">' . number_format($v['cost'], 2) . '</span>' : '0.00'; ?></td>
										<td class="text-right text-purple"><?php echo number_format($pricediff, 2); ?></td>
										
									</tr>
								<?php } ?>
								<tr>
									<td colspan="6"></td>
									<td class="text-right"><b><?php echo number_format($priceSum, 2); ?></b></td>
									<td class="text-right"><b><?php echo number_format($price_next, 2); ?></b></td>
									<td class="text-right"><b><?php echo number_format($costSum, 2); ?></b></td>
									<td class="text-right"><b><?php echo number_format($totalSum, 2); ?></b></td>
									<td colspan="1"></td>
								</tr>
							</tbody>
						</table>

                        </div>
                    </div>
                </div>
            <?php  }*/ ?>

            


            <?php /* if ($aOrderList['num_rows'] > 0) { ?>
                <div class="panel">
                    <div class="panel-body">
                        <div class="table-responsive">

                            <h2 class="text-main text-bold">ORDER</h2>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="100">Order ID</th>
                                        <th>Order Name</th>
                                        <th class="text-center" width="110">Item</th>
                                        <th width="110">Amount</th>
                                        <th class="text-center" width="90">หมดอายุ</th>
                                        <th class="text-center" width="150">Due Date</th>
                                        <th class="text-center" width="150">Status</th>
                                        <th class="text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aOrderList['data'] as $v) {
                                        $items = DB_LIST('billing_master_order_items', ['master_order_id' => $v['master_order_id']]);
                                        $diffdate = DifDate_billing($v['due_date']); ?>
                                        <tr>
                                            <td class="text-center"><?php echo $v['master_order_id'] ?></td>
                                            <td><a href="<?php echo _admin_buil_link('index.php?module=billing&mp=orderMaster_view&orid=' . $v['master_order_id'] . '&otb=userList', true); ?>" class="btn-link"><?php echo $v['title'] ?></a> <span class="text-danger"><?php echo $v['comment'] ?></span></td>
                                            <td class="text-center"><?php echo $items['num_rows'] . ' รายการ' ?></td>
                                            <td><?php echo number_format($v['total_amount'], 2) ?></td>
                                            <td class="text-center"><?php echo $diffdate ?></td>
                                            <td class="text-center"><span class="text-purple"><i class="demo-pli-clock"></i> <?php echo date('d/m/Y', strtotime($v['due_date'])) ?></span></td>
                                            <td class="text-center">
                                                <select class="form-control" disabled>
                                                    <option <?php echo ($v['status_order'] == 'active') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=active&orid=' .  $v['master_order_id']); ?>">ใช้งานอยู่</option>
                                                    <option <?php echo ($v['status_order'] == 'superseded') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=superseded&orid=' .  $v['master_order_id']); ?>">ระงับการใช้งาน</option>
                                                    <option <?php echo ($v['status_order'] == 'terminated') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=terminated&orid=' .  $v['master_order_id']); ?>">ยกเลิกการใช้งาน</option>
                                                </select>
                                            </td>
                                            <td class="text-right">
                                                <div class="btn-group">
                                                    <?php if ($v['status_order'] == 'active') { ?>
                                                        <a href="<?php echo _admin_buil_link('index.php?module=billing&mp=orderMaster_view&orid=' . $v['master_order_id'] . '&otb=userList', true); ?>" class="btn btn-info">Paid and View</a>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            <?php } */ ?>


            <?php /* if ($aInvoiceList['num_rows'] > 0) { ?>
                <div class="panel">
                    <div class="panel-body">
                        <div class="table-responsive">

                            <h2 class="text-main text-bold">INVOICE</h2>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="100">INV ID</th>
                                        <th>Order Name</th>
                                        <th class="text-center" width="110">Item</th>
                                        <th width="110">Amount</th>
                                        <th class="text-center" width="90">หมดอายุ</th>
                                        <th class="text-center" width="150">Due Date</th>
                                        <th class="text-center" width="150">Status</th>
                                        <th class="text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aInvoiceList['data'] as $v) {
                                        $items = DB_LIST('billing_master_order_items', ['master_order_id' => $v['master_order_id']]);
                                        $diffdate = DifDate_billing($v['due_date']); ?>
                                        <tr>
                                            <td class="text-center"><b><?php echo $v['reference_code'] ?></b></td>
                                            <td><a href="<?php echo _admin_buil_link('index.php?module=billing&mp=orderMaster_view&orid=' . $v['master_order_id'] . '&otb=userList', true); ?>" class="btn-link"><?php echo $v['title'] ?></a> <span class="text-danger"><?php echo $v['comment'] ?></span></td>
                                            <td class="text-center"><?php echo $items['num_rows'] . ' รายการ' ?></td>
                                            <td><?php echo number_format($v['total_amount'], 2) ?></td>
                                            <td class="text-center"><?php echo $diffdate ?></td>
                                            <td class="text-center"><span class="text-purple"><i class="demo-pli-clock"></i> <?php echo date('d/m/Y', strtotime($v['due_date'])) ?></span></td>
                                            <td class="text-center">
                                                <select class="form-control" disabled>
                                                    <option <?php echo ($v['status_order'] == 'active') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=active&orid=' .  $v['master_order_id']); ?>">ใช้งานอยู่</option>
                                                    <option <?php echo ($v['status_order'] == 'superseded') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=superseded&orid=' .  $v['master_order_id']); ?>">ระงับการใช้งาน</option>
                                                    <option <?php echo ($v['status_order'] == 'terminated') ? 'selected' : ''; ?> value="<?php echo _admin_buil_link('index.php?module=billing&mp=order&ac=change&status=terminated&orid=' .  $v['master_order_id']); ?>">ยกเลิกการใช้งาน</option>
                                                </select>
                                            </td>
                                            <td class="text-right">
                                                <div class="btn-group">
                                                    <?php if ($v['status_order'] == 'active') { ?>
                                                        <a href="<?php echo _admin_buil_link('index.php?module=billing&mp=orderMaster_view&orid=' . $v['master_order_id'] . '&otb=userList', true); ?>" class="btn btn-info">Paid and View</a>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            <?php } */ ?>

        </div>
    </div>
</div>