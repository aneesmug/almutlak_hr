<?php
// Employee Milestone card (service years / months / days, next-anniversary bar, optional
// estimated EOS). Shared by includes/emp_top_info.php (employee master) and profile.php.
// Needs $emprow; shows the EOS box only when $canViewEosValue and $eos_estimate are set.
?>
<?php if ($emprow["status"] == 1 && $emprow["fly"] == 0) : ?>
	<?php
	// Service milestone: years / months / days since joining + progress to the next work anniversary
	$etJoin = null;
	try {
		if (!empty($emprow['joining_date']) && $emprow['joining_date'] !== '0000-00-00') {
			$etJoin = new DateTime(substr((string) $emprow['joining_date'], 0, 10));
		}
	} catch (Exception $e) {
		$etJoin = null;
	}
	$etToday = new DateTime('today');
	if ($etJoin && $etJoin <= $etToday) {
		$etDiff = $etJoin->diff($etToday);
		$etNextYears = $etDiff->y + 1;
		$etNext = (clone $etJoin)->modify('+' . $etNextYears . ' years');
		$etPrev = (clone $etJoin)->modify('+' . $etDiff->y . ' years');
		$etDaysLeft = (int) $etToday->diff($etNext)->days;
		$etSpan = max(1, (int) $etPrev->diff($etNext)->days);
		$etProgress = max(0, min(100, round(($etSpan - $etDaysLeft) / $etSpan * 100)));
	}
	?>
	<style>
		.emp-tenure {
			position: relative; display: flex; align-items: center; flex-wrap: wrap; gap: 14px 22px;
			margin: 0 0 18px; padding: 16px 52px 16px 20px; border-radius: 16px; overflow: hidden;
			background: #fff; border: 1px solid rgba(15, 23, 42, .07);
			box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .06);
		}
		[dir="rtl"] .emp-tenure { padding: 16px 20px 16px 52px; }
		.emp-tenure::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: linear-gradient(180deg, #6366f1, #a855f7); }
		[dir="rtl"] .emp-tenure::before { inset: 0 0 0 auto; }
		.emp-tenure .et-main { display: flex; align-items: center; gap: 14px; flex: 1 1 280px; min-width: 0; }
		.emp-tenure .et-icon {
			flex: none; width: 50px; height: 50px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center;
			font-size: 22px; color: #fff; background: linear-gradient(135deg, #6366f1, #a855f7); box-shadow: 0 8px 18px rgba(99, 102, 241, .3);
		}
		.emp-tenure .et-head { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
		.emp-tenure .et-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #6366f1; }
		.emp-tenure .et-eyebrow i { margin-inline-end: 4px; }
		.emp-tenure .et-title { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.25; }
		.emp-tenure .et-sub { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; font-size: 12.5px; color: #64748b; }
		.emp-tenure .et-pill { display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
		.emp-tenure .et-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
		.emp-tenure .et-stats { display: flex; gap: 8px; }
		.emp-tenure .et-stat { min-width: 66px; padding: 8px 10px; border-radius: 12px; text-align: center; background: #f5f3ff; border: 1px solid #ede9fe; }
		.emp-tenure .et-stat b { display: block; font-size: 20px; font-weight: 800; color: #4f46e5; line-height: 1.1; font-variant-numeric: tabular-nums; }
		.emp-tenure .et-stat small { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
		.emp-tenure .et-next { flex: 1 1 220px; min-width: 200px; }
		.emp-tenure .et-next-head { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 6px; font-size: 12px; color: #64748b; font-weight: 600; }
		.emp-tenure .et-next-head b { color: #0f172a; white-space: nowrap; }
		.emp-tenure .et-bar { height: 8px; border-radius: 999px; background: #eef1f5; overflow: hidden; }
		.emp-tenure .et-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6366f1, #a855f7); }
		.emp-tenure .et-eos { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; }
		.emp-tenure .et-eos > i { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #fef3c7; color: #b45309; }
		.emp-tenure .et-eos small { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #92400e; }
		.emp-tenure .et-eos b { font-size: 15px; font-weight: 800; color: #78350f; font-variant-numeric: tabular-nums; white-space: nowrap; }
		.emp-tenure .et-close {
			position: absolute; top: 10px; inset-inline-end: 10px; width: 30px; height: 30px; border: 0; border-radius: 9px;
			background: transparent; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
		}
		.emp-tenure .et-close:hover { background: #f1f5f9; color: #0f172a; }
		html.app-dark .emp-tenure { background: #242a35; border-color: #323a48; }
		html.app-dark .emp-tenure .et-title, html.app-dark .emp-tenure .et-next-head b { color: #e5e7eb; }
		html.app-dark .emp-tenure .et-stat { background: rgba(99, 102, 241, .12); border-color: rgba(99, 102, 241, .25); }
		html.app-dark .emp-tenure .et-stat b { color: #a5b4fc; }
		html.app-dark .emp-tenure .et-bar { background: #1b1f27; }
		html.app-dark .emp-tenure .et-eos { background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .3); }
		html.app-dark .emp-tenure .et-eos b { color: #fcd34d; }
		html.app-dark .emp-tenure .et-eos small { color: #fbbf24; }
		html.app-dark .emp-tenure .et-close:hover { background: #323a48; color: #e5e7eb; }
		@media (max-width: 575px) { .emp-tenure .et-stats { width: 100%; } .emp-tenure .et-stat { flex: 1; } }
	</style>
	<div class="emp-tenure">
		<button type="button" class="et-close" title="<?= __('close', 'Close') ?>" onclick="this.closest('.emp-tenure').style.display='none'"><i class="fa fa-times"></i></button>

		<div class="et-main">
			<span class="et-icon"><i class="fa fa-award"></i></span>
			<div class="et-head">
				<span class="et-eyebrow"><i class="fa fa-sparkles"></i><?= __('employee_milestone', 'Employee Milestone') ?></span>
				<?php if (!empty($etDiff)): ?>
					<span class="et-title">
						<?php if ($etDiff->y > 0): ?>
							<?= $etDiff->y ?> <?= $etDiff->y === 1 ? __('year_with_us', 'year with us') : __('years_with_us', 'years with us') ?>
						<?php else: ?>
							<?= __('welcome_to_the_team', 'Welcome to the team') ?>
						<?php endif; ?>
					</span>
					<span class="et-sub">
						<span><i class="fa fa-calendar-day"></i> <?= __('joined', 'Joined') ?> <?= $etJoin->format('d M Y') ?></span>
						<span class="et-pill"><?= __('active', 'Active') ?></span>
					</span>
				<?php else: ?>
					<span class="et-title"><?= __('happy_life_with_us') . " " . ageDOB($emprow['joining_date']) ?></span>
					<span class="et-sub"><span class="et-pill"><?= __('active', 'Active') ?></span></span>
				<?php endif; ?>
			</div>
		</div>

		<?php if (!empty($etDiff)): ?>
		<div class="et-stats">
			<div class="et-stat"><b><?= $etDiff->y ?></b><small><?= __('years', 'Years') ?></small></div>
			<div class="et-stat"><b><?= $etDiff->m ?></b><small><?= __('months', 'Months') ?></small></div>
			<div class="et-stat"><b><?= $etDiff->d ?></b><small><?= __('days', 'Days') ?></small></div>
		</div>

		<div class="et-next">
			<div class="et-next-head">
				<span><?= __('next_anniversary', 'Next anniversary') ?>: <?= $etNextYears ?> <?= $etNextYears === 1 ? __('year', 'year') : __('years_lc', 'years') ?></span>
				<b><?= $etDaysLeft === 0 ? __('today', 'Today') : __('in', 'in') . ' ' . $etDaysLeft . ' ' . __('days_lc', 'days') ?></b>
			</div>
			<div class="et-bar"><span style="width: <?= (int) $etProgress ?>%"></span></div>
		</div>
		<?php endif; ?>

		<?php if (!empty($canViewEosValue) && !empty($eos_estimate['success'])): ?>
		<div class="et-eos">
			<i class="fa fa-hand-holding-dollar"></i>
			<div>
				<small><?= __('estimated_eos_value', 'Estimated EOS') ?></small>
				<b><?= number_format($eos_estimate['eos_amount'], 2) ?> <i class="icon-saudi_riyal"></i></b>
			</div>
		</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
