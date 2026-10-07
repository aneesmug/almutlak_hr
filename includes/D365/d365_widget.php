<?php
// Microsoft Dynamics 365 status widget (status card + Sync / Add to D365 buttons).
// Used by includes/emp_top_info.php (view / edit employee header) and profile.php.
// Expects $conDB, $emprow; optional $d365WidgetClass (extra CSS class, e.g. 'is-inline'),
// $d365WidgetStatusOnly = true to show the status card only (no Sync / Add buttons, e.g. profile.php).
// Status is shown to everyone; Sync / Add need system admin or the 'd365_sync_employee' special access
// (enforced again in includes/ajaxFile/d365_employee.php).
$d365CanSync = empty($d365WidgetStatusOnly) && user_has_special_access($conDB, $logged_in_empid ?? ($empid ?? ''), 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (empty($_SESSION['d365_csrf'])) {
	$_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
}
?>
<div id="d365Widget" class="d365-widget <?= htmlspecialchars($d365WidgetClass ?? '') ?>" data-emp="<?= htmlspecialchars($emprow['empid']) ?>" data-csrf="<?= htmlspecialchars($_SESSION['d365_csrf']) ?>" data-can-sync="<?= $d365CanSync ? '1' : '0' ?>">
	<span class="d365-pill is-loading"><span class="d365-logo"><i></i><i></i><i></i><i></i></span><span class="d365-txt"><b>Microsoft Dynamics 365</b><small><i class="fa fa-spinner fa-spin"></i> Checking...</small></span></span>
</div>
<style>
	.d365-widget { position: absolute; right: 100%; top: 50%; transform: translateY(-50%); margin-right: 10px; display: flex; gap: 8px; align-items: center; white-space: nowrap; }
	[dir="rtl"] .d365-widget { right: auto; left: 100%; margin-right: 0; margin-left: 10px; }
	/* Status card: Microsoft logo + full product name + company / state line */
	.d365-widget .d365-pill { display: inline-flex; align-items: center; gap: 10px; padding: 6px 14px 6px 10px; border-radius: 10px; background: #fff; color: #1e293b; border: 1px solid rgba(15,23,42,.08); border-left: 4px solid #94a3b8; box-shadow: 0 2px 8px rgba(15,23,42,.18); line-height: 1.15; text-align: left; cursor: default; }
	[dir="rtl"] .d365-widget .d365-pill { border-left-width: 1px; border-right: 4px solid #94a3b8; text-align: right; padding: 6px 10px 6px 14px; }
	.d365-widget .d365-pill.is-ok { border-left-color: #16a34a; }
	.d365-widget .d365-pill.is-bad { border-left-color: #dc2626; cursor: help; }
	[dir="rtl"] .d365-widget .d365-pill.is-ok { border-right-color: #16a34a; }
	[dir="rtl"] .d365-widget .d365-pill.is-bad { border-right-color: #dc2626; }
	.d365-widget .d365-logo { display: grid; grid-template-columns: 9px 9px; gap: 2px; flex: none; }
	.d365-widget .d365-logo i { width: 9px; height: 9px; display: block; }
	.d365-widget .d365-logo i:nth-child(1) { background: #f25022; }
	.d365-widget .d365-logo i:nth-child(2) { background: #7fba00; }
	.d365-widget .d365-logo i:nth-child(3) { background: #00a4ef; }
	.d365-widget .d365-logo i:nth-child(4) { background: #ffb900; }
	.d365-widget .d365-txt { display: flex; flex-direction: column; gap: 2px; }
	.d365-widget .d365-txt b { font-size: 12.5px; font-weight: 700; letter-spacing: .1px; color: #0f172a; }
	.d365-widget .d365-txt small { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #64748b; }
	.d365-widget .d365-dot { width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; flex: none; }
	.d365-widget .is-ok .d365-dot { background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.18); }
	.d365-widget .is-bad .d365-dot { background: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.18); }
	.d365-widget .is-ok .d365-txt small { color: #15803d; }
	.d365-widget .is-bad .d365-txt small { color: #b91c1c; }
	.d365-widget .more-actions-btn { padding: 8px 14px; }
	.d365-widget .more-actions-btn.is-warn { background: rgba(245,158,11,.9); border-color: rgba(255,255,255,.4); }
	/* Inline variant (profile.php action row): flows with the other buttons */
	.d365-widget.is-inline, [dir="rtl"] .d365-widget.is-inline { position: static; transform: none; margin: 0; }
	@media (max-width: 991px) { .d365-widget { position: static; transform: none; margin: 0 0 8px; justify-content: center; } }
</style>
<script>
(function () {
	var box = document.getElementById('d365Widget');
	if (!box) return;
	var empId = box.getAttribute('data-emp');
	var csrf = box.getAttribute('data-csrf');
	var canSync = box.getAttribute('data-can-sync') === '1';
	var ENDPOINT = './includes/ajaxFile/d365_employee.php';
	var last = null;

	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	var POP = { popup: 'sr-addline-popup sr-page' };
	// New GUI popups need smart_request.css + icons.css (not every page that includes this header links them)
	function ensureCss() {
		[['smart_request.css', 'assets/css/smart_request.css'], ['icons.css', 'assets/css/icons.css']].forEach(function (c) {
			if (document.querySelector('link[href*="' + c[0] + '"]')) return;
			var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = c[1]; document.head.appendChild(l);
		});
	}
	function withSwal(cb) {
		ensureCss();
		if (window.Swal) { cb(); return; }
		var s = document.createElement('script');
		s.src = './plugins/sweet-alert/v11/sweetalert2.all.min.js';
		s.onload = cb;
		document.head.appendChild(s);
	}
	function call(action, extra) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('csrf', csrf);
		fd.append('emp_id', empId);
		Object.keys(extra || {}).forEach(function (k) { fd.append(k, extra[k]); });
		return fetch(ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { throw new Error('Server error (HTTP ' + r.status + ')'); }); });
	}

	// Status card: tone = ok / bad / loading, line = company / state text (already escaped)
	function pill(tone, line, title) {
		return '<span class="d365-pill is-' + tone + '"' + (title ? ' title="' + esc(title) + '"' : '') + '>'
			+ '<span class="d365-logo"><i></i><i></i><i></i><i></i></span>'
			+ '<span class="d365-txt"><b>Microsoft Dynamics 365</b><small>' + (tone === 'loading' ? '<i class="fa fa-spinner fa-spin"></i>' : '<span class="d365-dot"></span>') + line + '</small></span></span>';
	}

	function render(res) {
		last = res;
		if (!res || (res.ok === false && !res.status)) {
			box.innerHTML = pill('bad', 'Status unavailable', res && res.error)
				+ '<button type="button" class="more-actions-btn" data-act="refresh" title="Retry"><i class="fa fa-redo"></i></button>';
			return;
		}
		var st = res.status || {};
		var env = (res.environment || '').toUpperCase();
		var writeAttr = res.can_write ? '' : ' disabled title="Writes are off (App Settings > D365 Config)"';
		if (st.status === 'registered') {
			var info = 'Microsoft Dynamics 365 (' + env + ') worker ' + (st.d365_name || empId)
				+ (st.synced_at ? '\nLast sync: ' + st.synced_at : '\nNot synced from the app yet')
				+ (st.last_error ? '\n' + st.last_error : '');
			var line = (st.legal_entity ? esc(st.legal_entity) + ' · ' : '') + (st.last_error ? 'Sync error' : (st.synced_at ? 'Synced' : 'Registered'));
			box.innerHTML = pill(st.last_error ? 'bad' : 'ok', line, info)
				+ (canSync ? '<button type="button" class="more-actions-btn" data-act="sync"' + writeAttr + '><i class="fa fa-sync-alt"></i> Sync to D365</button>' : '');
		} else {
			var why = st.status === 'missing' ? 'Not registered in Microsoft Dynamics 365 ' + env : (st.last_error || 'Registration failed');
			box.innerHTML = pill('bad', 'Not registered' + (env ? ' · ' + esc(env) : ''), why)
				+ (canSync ? '<button type="button" class="more-actions-btn is-warn" data-act="register"' + writeAttr + '><i class="fa fa-plus"></i> Add to D365</button>' : '');
		}
	}

	function load(refresh) {
		box.innerHTML = pill('loading', 'Checking...');
		call('status', refresh ? { refresh: 1 } : {}).then(render).catch(function (e) { render({ ok: false, error: e.message }); });
	}

	function doRegister() {
		var sug = (last && last.suggest) || { company: '', entities: [] };
		withSwal(function () {
			var opts = '<option value="">- choose -</option>' + (sug.entities || []).map(function (en) {
				var nm = (sug.names || {})[en];
				return '<option value="' + esc(en) + '"' + (en === sug.company ? ' selected' : '') + '>' + esc(en + (nm ? ' - ' + nm : '')) + '</option>';
			}).join('');
			var env = esc((last.environment || '').toUpperCase());
			Swal.fire({
				title: 'Add ' + esc(empId) + ' to D365',
				html: '<div class="sr-form">'
					+ '<div class="sr-notice tone-sky" style="margin-bottom:12px"><i class="mdi mdi-information-outline"></i><div>Creates the worker and employment in D365 <b>' + env + '</b> from this employee&#39;s HR data.</div></div>'
					+ (last.status && last.status.last_error ? '<div class="sr-notice tone-red" style="margin-bottom:12px"><i class="mdi mdi-alert-circle-outline"></i><div><b>Last error:</b> ' + esc(last.status.last_error) + '</div></div>' : '')
					+ '<div class="sr-fsec mb-0">'
					+ '<div class="sr-fsec-head"><span><i class="mdi mdi-domain"></i> D365 worker</span>' + (env ? '<span class="sr-pill sr-pill-xs ' + (env === 'PROD' || env === 'PRODUCTION' ? 'tone-red' : 'tone-amber') + '">' + env + '</span>' : '') + '</div>'
					+ '<div class="sr-fgrid">'
					+ '<div class="sr-fcol c-6"><label>Employee ID</label><input type="text" class="form-control" value="' + esc(empId) + '" readonly></div>'
					+ '<div class="sr-fcol c-6"><label for="d365Company">D365 company <span class="text-danger">*</span></label><select id="d365Company" class="form-control">' + opts + '</select>'
					+ (sug.company ? '<span class="sr-fhint">Suggested from the app company: <b>' + esc(sug.company) + '</b></span>' : '') + '</div>'
					+ '</div></div></div>',
				showCancelButton: true,
				confirmButtonText: '<i class="mdi mdi-account-plus"></i> Add to D365',
				confirmButtonColor: (window.APP_COLORS && APP_COLORS.primary) || undefined,
				cancelButtonColor: (window.APP_COLORS && APP_COLORS.danger_dark) || undefined,
				width: '560px',
				customClass: POP,
				allowOutsideClick: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					var company = document.getElementById('d365Company').value;
					if (!company) { Swal.showValidationMessage('Choose the D365 company'); return false; }
					return call('register', { company: company }).then(function (res) {
						if (!res.ok) { Swal.showValidationMessage(res.error || 'Failed'); if (res.status) render(res); return false; }
						return res;
					}).catch(function (e) { Swal.showValidationMessage(e.message); return false; });
				}
			}).then(function (r) {
				if (!r.isConfirmed) return;
				render(r.value);
				if (r.value.warning) {
					Swal.fire({ icon: 'warning', title: 'Added to D365', text: r.value.warning, customClass: POP });
				} else {
					Swal.fire({ icon: 'success', title: 'Added to D365', text: 'Worker, employment, contact details and bank account created.', timer: 2200, showConfirmButton: false, customClass: POP });
				}
				// D365 tab (view_employee.php) re-reads D365 so it shows the new worker
				document.dispatchEvent(new CustomEvent('d365:synced', { detail: { emp: empId } }));
			});
		});
	}

	function doSync() {
		withSwal(function () {
			var fields = ['Name', 'Birth date', 'Gender', 'Email', 'Mobile', 'Marital status', 'Salary bank account (IBAN)', 'Department (when blank in D365)'];
			Swal.fire({
				title: 'Sync ' + esc(empId) + ' to D365?',
				html: '<div class="sr-form">'
					+ '<div class="sr-fsec mb-0">'
					+ '<div class="sr-fsec-head"><span><i class="mdi mdi-cloud-sync"></i> Sent from the HR app</span>' + (last && last.environment ? '<span class="sr-pill sr-pill-xs tone-amber">' + esc(String(last.environment).toUpperCase()) + '</span>' : '') + '</div>'
					+ '<div class="sr-fsec-body"><div class="d-flex flex-wrap" style="gap:6px">'
					+ fields.map(function (f) { return '<span class="sr-chip"><i class="mdi mdi-check"></i> ' + f + '</span>'; }).join('')
					+ '</div><span class="sr-fhint mt-2">These values overwrite the D365 worker record.</span></div>'
					+ '</div></div>',
				showCancelButton: true,
				confirmButtonText: '<i class="mdi mdi-cloud-sync"></i> Sync',
				confirmButtonColor: (window.APP_COLORS && APP_COLORS.primary) || undefined,
				cancelButtonColor: (window.APP_COLORS && APP_COLORS.danger_dark) || undefined,
				width: '560px',
				customClass: POP,
				allowOutsideClick: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					return call('sync').then(function (res) {
						if (!res.ok) { Swal.showValidationMessage(res.error || 'Failed'); return false; }
						return res;
					}).catch(function (e) { Swal.showValidationMessage(e.message); return false; });
				}
			}).then(function (r) {
				if (!r.isConfirmed) return;
				render(r.value);
				var bankText = { created: 'Bank account added.', updated: 'Bank account IBAN updated.', unchanged: 'Bank account already up to date.' }[r.value.bank] || '';
				if (r.value.department === 'set') bankText += ' Department set to ' + r.value.department_value + '.';
				if (r.value.warning) {
					Swal.fire({ icon: 'warning', title: 'Synced to D365', text: r.value.warning, customClass: POP });
				} else {
					Swal.fire({ icon: 'success', title: 'Synced to D365', text: bankText, timer: 2200, showConfirmButton: false, customClass: POP });
				}
				// D365 tab (view_employee.php) re-reads D365 so it shows the new values
				document.dispatchEvent(new CustomEvent('d365:synced', { detail: { emp: empId } }));
			});
		});
	}
	// Change company: end the current D365 employment and start one in the new company (D365Workers::transferCompany)
	function doTransfer() {
		withSwal(function () {
			Swal.fire({ title: 'Loading D365 employment...', allowOutsideClick: false, customClass: POP, didOpen: function () { Swal.showLoading(); } });
			call('transfer_info').then(function (info) {
				if (!info.ok) throw new Error(info.error || 'Failed');
				var active = (info.employments || []).filter(function (e) { return e.active; });
				var cur = active[0] ? active[0].LegalEntityId : '';
				var env = esc((info.environment || '').toUpperCase());
				var today = new Date(); today.setDate(today.getDate() + 1);
				var tomorrow = today.toISOString().slice(0, 10);
				var hist = (info.employments || []).map(function (e) {
					return '<tr><td><b>' + esc(e.LegalEntityId) + '</b></td><td>' + esc(e.start_local) + '</td><td>' + (e.end_local ? esc(e.end_local) : '<span class="sr-pill tone-green"><span class="sr-dot"></span>Active</span>') + '</td><td class="sr-mono">' + esc(e.DimensionDisplayValue || '') + '</td></tr>';
				}).join('');
				var opts = '<option value="">- choose -</option>' + (info.companies || []).filter(function (c) { return c.code !== cur; }).map(function (c) {
					return '<option value="' + esc(c.code) + '">' + esc(c.code + (c.name ? ' - ' + c.name : '')) + '</option>';
				}).join('');
				Swal.fire({
					title: 'Change D365 company - ' + esc(empId),
					width: '680px',
					customClass: POP,
					allowOutsideClick: false,
					showCancelButton: true,
					confirmButtonText: '<i class="fa fa-exchange-alt"></i> Change company',
					showLoaderOnConfirm: true,
					html: '<div class="sr-form">'
						+ '<div class="sr-notice tone-amber" style="margin-bottom:12px"><i class="mdi mdi-alert-outline"></i><div>Writes to D365 <b>' + env + '</b>: the employment in <b>' + esc(cur || '?') + '</b> ends the day before the transfer date and a new employment starts in the chosen company (same financial dimensions). Positions are not moved.</div></div>'
						+ '<div class="sr-fsec"><div class="sr-fsec-head"><span><i class="mdi mdi-history"></i> Employment history</span></div>'
						+ '<div class="sr-table-wrap"><table class="sr-table"><thead><tr><th>Company</th><th>Start</th><th>End</th><th>Dimensions</th></tr></thead><tbody>' + (hist || '<tr><td colspan="4">None</td></tr>') + '</tbody></table></div></div>'
						+ '<div class="sr-fsec mb-0"><div class="sr-fgrid">'
						+ '<div class="sr-fcol c-6"><label>Current company</label><input type="text" class="form-control" value="' + esc(cur || '-') + '" readonly></div>'
						+ '<div class="sr-fcol c-6"><label for="d365NewCompany">New company <span class="text-danger">*</span></label><select id="d365NewCompany" class="form-control">' + opts + '</select></div>'
						+ '<div class="sr-fcol c-6"><label for="d365TransferDate">Transfer date (first day in new company) <span class="text-danger">*</span></label><input type="date" id="d365TransferDate" class="form-control" value="' + tomorrow + '"></div>'
						+ '</div></div></div>',
					preConfirm: function () {
						var company = document.getElementById('d365NewCompany').value;
						var date = document.getElementById('d365TransferDate').value;
						if (!cur) { Swal.showValidationMessage('No active employment in D365'); return false; }
						if (!company) { Swal.showValidationMessage('Choose the new company'); return false; }
						if (!date) { Swal.showValidationMessage('Choose the transfer date'); return false; }
						return call('transfer', { company: company, date: date }).then(function (res) {
							if (!res.ok) { Swal.showValidationMessage(res.error || 'Failed'); return false; }
							return res;
						}).catch(function (e) { Swal.showValidationMessage(e.message); return false; });
					}
				}).then(function (r) {
					if (!r.isConfirmed) return;
					render(r.value);
					document.dispatchEvent(new CustomEvent('d365:synced', { detail: { emp: empId } }));
					Swal.fire({ icon: r.value.warning ? 'warning' : 'success', title: 'Moved ' + esc(r.value.from) + ' → ' + esc(r.value.to),
						text: r.value.warning || 'New employment created in D365.', customClass: POP, allowOutsideClick: false })
						.then(function () { location.reload(); }); // profile D365 block shows the new company
				});
			}).catch(function (e) { Swal.fire({ icon: 'error', title: 'D365', text: e.message, customClass: POP }); });
		});
	}

	// D365 tab "Sync to D365" button uses the same flow
	document.addEventListener('d365:sync-request', function () { if (canSync) doSync(); });
	document.addEventListener('d365:register-request', function () { if (canSync) doRegister(); });
	// Profile "Dynamics 365" block (view_employee.php) "Change company" button
	document.addEventListener('d365:transfer-request', function () {
		if (!canSync) return;
		if (last && last.can_write === false) {
			withSwal(function () { Swal.fire({ icon: 'info', title: 'D365', text: 'Writes are off (App Settings > D365 Config > Allow Writes)', customClass: POP }); });
			return;
		}
		doTransfer();
	});

	box.addEventListener('click', function (ev) {
		var b = ev.target.closest('[data-act]');
		if (!b || b.disabled) return;
		var act = b.getAttribute('data-act');
		if (act === 'register') { if (canSync) doRegister(); }
		else if (act === 'sync') { if (canSync) doSync(); }
		else if (act === 'transfer') { if (canSync) doTransfer(); }
		else load(true);
	});
	load(false);
})();
</script>
