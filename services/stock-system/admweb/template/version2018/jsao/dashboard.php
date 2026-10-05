    <!--Flot Chart [ OPTIONAL ]-->
    <!-- <script src="<?php //cho TEMPLATE_URL; 
                        ?>/plugins/flot-charts/jquery.flot.min.js?v=<?php //echo CACHE_VERSION; 
                                                                    ?>"></script>
	<script src="<?php //echo TEMPLATE_URL; 
                    ?>/plugins/flot-charts/jquery.flot.resize.min.js?v=<?php //echo CACHE_VERSION; 
                                                                        ?>"></script>
	<script src="<?php //echo TEMPLATE_URL; 
                    ?>/plugins/flot-charts/jquery.flot.tooltip.min.js?v=<?php //echo CACHE_VERSION; 
                                                                        ?>"></script> -->

    <!--Sparkline [ OPTIONAL ]-->
    <!-- <script src="<?php //echo TEMPLATE_URL; 
                        ?>/plugins/sparkline/jquery.sparkline.min.js?v=<?php //echo CACHE_VERSION; 
                                                                        ?>"></script>-->

    <!--Specify page [ SAMPLE ]-->
    <!--<script src="<?php //echo TEMPLATE_URL; 
                        ?>/js/demo/dashboard.js?v=<?php //echo CACHE_VERSION; 
                                                    ?>"></script> -->

    <?php
    if (in_array('counter', $aModuleUse)) {
        $cy = date('Y');
        $cys = $cy - 1;

        $aM = Arrays_months('th');
        foreach ($aM as $k => $v) {
            $aCounter = func_counter_get($k . '-' . $cys, 'web');
            $monthOld[$k] = isset($aCounter['month'][0]) ? $aCounter['month'][0] : 0;

            $aCounter = func_counter_get($k . '-' . $cy, 'web');
            $month[$k] = isset($aCounter['month'][0]) ? $aCounter['month'][0] : 0;
        }

        $strCountOld = implode(',', $monthOld);
        $strCountCur = implode(',', $month);
    ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/highcharts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/highcharts-more.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/modules/solid-gauge.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.0/modules/accessibility.js"></script>
        <script>
            var chart = Highcharts.chart('statsChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'สถิติการเข้าใช้งานเว็บไซต์'
                },
                subtitle: {
                    text: 'ข้อมูลการเปิดหน้าเว็บไซต์ และ ระบบหลังบ้าน'
                },
                credits: {
                    enabled: false
                },
                legend: {
                    align: 'right',
                    verticalAlign: 'middle',
                    layout: 'vertical'
                },
                xAxis: {
                    categories: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                    labels: {
                        x: -10
                    }
                },
                yAxis: {
                    allowDecimals: false,
                    title: {
                        text: 'จำนวนผู้เข้าใช้งานรายเดือน'
                    }
                },
                series: [{
                    name: 'สถิติปี <?php echo $cys; ?>',
                    data: [<?php echo $strCountOld; ?>]
                }, {
                    name: 'สถิติปี <?php echo $cy; ?>',
                    data: [<?php echo $strCountCur; ?>]
                }],

                responsive: {
                    rules: [{
                        condition: {
                            maxWidth: 1100
                        },
                        chartOptions: {
                            legend: {
                                align: 'center',
                                verticalAlign: 'bottom',
                                layout: 'horizontal'
                            },
                            yAxis: {
                                labels: {
                                    align: 'left',
                                    x: 0,
                                    y: -5
                                }
                            }
                        }
                    }]
                }
            });
        </script>
    <?php } ?>