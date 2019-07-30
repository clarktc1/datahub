<?php
$base_url=base_url();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Api Error Logs</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo $base_url.'asset/css2/bootstrap.min.css';?>" rel="stylesheet">
        <script src="<?php echo $base_url.'asset/js2/bootstrap.min.js'?>"></script>
        <style type="text/css">
        	tr th { min-width: 125px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="column">
            	<h3>Api Error Log List</h3>
            	<div class="table-responsive">
	                <table class="table-hover table-condensed table-bordered">
	                    <thead>
	                        <tr>
	                            <th style="min-width: 60px;">ID</th>
	                            <th>Table Name</th>
	                            <th style="min-width: 65px;">User Id</th>
	                            <th>Data</th>
	                            <th>Amazon Order Id</th>
	                            <th style="min-width: 100px;">Api Date</th>
                                <th style="min-width: 147px;">Insert Date</th>
	                        </tr>
	                    </thead>
	                    <tbody>
	                        <?php foreach ($logs as $log): ?>
	                            <tr>
	                                <td><?= $log->id ?></td>
	                                <td><?= $log->table_name ?></td>
	                                <td><?= $log->user_id ?></td>
	                                <td><?= $log->data ?></td>
	                                <td><?= $log->amazon_order_id ?></td>
	                                <td><?= $log->api_date ?></td>
                                    <td><?= $log->insert_date ?></td>
	                            </tr>
	                        <?php endforeach; ?>
	                    </tbody>
	                </table>
	            </div>
            	<?php echo $links; ?>
            </div>
        </div>
    </body>
</html>
