<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?= htmlspecialchars($converted_object->name) ?> Wedding</title>
		<style>
			@page {
				size: A4;
				margin: 15mm;
			}
			html, body {
				margin: 0;
				padding: 0;
				-webkit-print-color-adjust: exact;
			}
			*, *::before, *::after {
				box-sizing: border-box;
			}
			p {
				margin: 0;
			}
			.page-wrapper {
				position: relative;
				min-height: 100vh;
				padding-bottom: 60px;
			}
			.container {
				width: 100%;
				color: #333;
				background: #fff;
			}
			.content-wrapper {
				position: relative;
				width: 100%;
			}
			.footer-bottom {
				position: absolute;
				bottom: 0;
				left: 0;
				width: 100%;
				text-align: center;
				padding: 15px 0 10px 0;
				color: #3C3B3B;
				font-family: 'Poppins', sans-serif;
				font-size: 12px;
				font-weight: 400;
			}
			table {
				page-break-inside: auto;
			}
			tr {
				page-break-inside: avoid;
				page-break-after: auto;
			}
			thead {
				display: table-row-group;
			}
			tfoot {
				display: table-footer-group;
			}
		</style>
	</head>
	<body>
		<div class="page-wrapper">
		<div class="container">
			<div class="content-wrapper">
				<div style="text-align: center; background-color: #fff;">
					<img class="footer-img" style="width: 280px; height: 120px; margin-top: 20px" src="{{ asset('img/pdf-logo.png') }}" alt="Barefoot Bridal">
					<hr style="border: none; border-top: 1px solid #000000; margin-top: 10px; width: 80%;">
				</div>
				<h1 style="text-align: center; font-size: 22px; color:#3C3B3B; font-family: 'Poppins'; font-weight: 600; margin-top: 20px;">
					<?= htmlspecialchars($converted_object->name) ?> Wedding
				</h1>
				<div style="text-align: center; font-size: 18px; color:#3C3B3B; font-family: 'Poppins'; font-weight: 600; margin-top: -10px;">
					<span>Wedding Date:</span>
					<span style="color: #995C64; margin-left: 5px; font-family: 'Poppins';"><?= htmlspecialchars($converted_object->wedding_date) ?></span>
				</div>
				<div style="text-align: center; padding-top: 20px; color:#3C3B3B; font-size: 14px; font-weight: 600; margin:0; font-family:'Poppins';">
					Resort: <span style="font-weight: 400; font-size: 12px; font-family: 'Poppins';"><?= htmlspecialchars($converted_object->resort) ?></span>
				</div>
				<div style="text-align: center; font-size: 14px; color:#3C3B3B; font-weight: 600; margin-bottom: 5px; font-family:'Poppins';">
					Travel Dates: <span style="font-weight: 400; font-size: 12px; font-family: 'Poppins';"><?= htmlspecialchars($converted_object->travel_dates) ?></span>
				</div>
				<?php foreach ($converted_object->rates as $rate): ?>
					<div style="margin: 0 auto; margin-top: 20px;">
						<div style="text-align: center; font-size: 14px; font-weight: 600; font-family:'Poppins';">
							Rates Valid: <span style="font-weight: 400; font-size: 12px; font-family: 'Poppins';"><?= htmlspecialchars($rate->rates_valid) ?></span>
						</div>
						<table style="width: 95%; border-collapse: collapse; margin: 0 auto; font-family:'Poppins'; font-size: 12px;">
							<thead>
								<tr style="background-color: #C7979C; color: #3C3B3B; font-family:'Poppins';">
									<th rowspan="2" style="padding-left:5px; color: #3C3B3B; text-align: left; vertical-align: middle; width: 40%; font-family: 'Poppins'; font-size: 14px; font-weight: 600; border: 1px solid #000000;"><?= htmlspecialchars($rate->header[0]) ?></th>
									<th colspan="<?= (count($rate->header) - 1) ?>" style="text-align: left; padding-top:5px; border: 1px solid #000000; color: #3C3B3B; font-family: 'Poppins'; font-size: 12px; font-weight: 600;">Price Per Person Per Night</th>
								</tr>
								<tr style="background-color: #C7979C; font-size: 14px; font-weight: 400; color: #3C3B3B; font-family:'Poppins';">
									<?php $header_index = 0; ?>
									<?php foreach ($rate->header as $header): ?>
										<?php if ($header_index == 0) { $header_index++; continue; } ?>
										<th style=" border: 1px solid #333; color: #3C3B3B; font-size: 12px; font-weight: 600; font-family: 'Poppins'; padding-top:5px; padding-left:5px; padding-right:5px;">
											<?= htmlspecialchars($header) ?>
										</th>
										<?php $header_index++; ?>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($rate->body as $body_row): ?>
									<tr style="font-family: Poppins; font-size: 13px; font-weight: 400;">
										<td style="padding-left:5px; padding-right:5px; text-align: left; font-family: 'Poppins'; font-size: 13px; font-weight: 400; border: 1px solid #000000; white-space: nowrap; color: #3C3B3B;">
											<?= htmlspecialchars($body_row[0]) ?>
										</td>
										<?php $body_index = 0; ?>
										<?php foreach ($body_row as $body_column): ?>
											<?php if ($body_index == 0) { $body_index++; continue; } ?>
											<td style="padding-left:5px; padding-right:5px; text-align: center; font-size: 13px; font-weight: 400; border: 1px solid #000000; color: #3C3B3B;">
												<?= htmlspecialchars($body_column) ?>
											</td>
											<?php $body_index++; ?>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
				<div style="text-align: center; font-size: 12px; color: #3C3B3B; font-weight: 200; margin: 0px; font-family:'Poppins'; font-style: italic;">
					Rates include all taxes and fees.<br>
					Rates and inventory are not guaranteed.<br>
					Proposal valid until <?= htmlspecialchars($converted_object->proposal_valid_until) ?>.
					<?php if ((int)$converted_object->min_nights > 3): ?>
						<br>
						<span style="color:crimson">
							<?= htmlspecialchars($converted_object->min_nights) ?> Night Minimum Length of Stay Required
						</span>
					<?php endif; ?>
				</div>
				<div style="font-size: 12px; padding-left: 10%; padding-right: 10%; font-weight: 200; margin-top: 20px; margin-bottom: 20px; font-family:'Poppins';">
					<strong style="font-size: 14px; font-weight: 400; font-family:'Poppins'; color: #3C3B3B;">CONCESSIONS</strong>
					<ul style="padding-left: 30px; margin-top: 15px; line-height:18px; color: #3C3B3B; font-style: italic;">
						<?php foreach ($converted_object->concessions as $parent_concession): ?>
							<?php if (is_array($parent_concession)): ?>
								<li><?= htmlspecialchars($parent_concession[0]) ?></li>
								<ul>
									<?php $concession_index = 0; ?>
									<?php foreach ($parent_concession as $child_concession): ?>
										<?php if ($concession_index == 0) { $concession_index++; continue; } ?>
										<li><?= htmlspecialchars($child_concession) ?></li>
										<?php $concession_index++; ?>
									<?php endforeach; ?>
								</ul>
							<?php else: ?>
								<li><?= htmlspecialchars($parent_concession) ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<div class="footer-bottom">
				<hr style="border: none; border-top: 1px solid #000000; color: #3C3B3B; margin: 0 auto; width: 80%;">
				<div style="margin-top: 5px;">
					<?php if ($converted_object->travel_agent): ?> <?= htmlspecialchars($converted_object->travel_agent) ?> • <?php endif; ?><?= htmlspecialchars('www.barefootbridal.com') ?> • <?= htmlspecialchars('866.822.7356') ?>
				</div>
			</div>
		</div>
		</div>
	</body>
</html>
