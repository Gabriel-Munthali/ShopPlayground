// Shared page initializers (optional libs guarded per page).
function initMainNavEnvSync($) {
	if (!document.getElementById('main-nav-env-sandbox') || !document.getElementById('offcanvas-main-nav-env-sandbox')) {
		return;
	}
	$(document).on('change', 'input[name="main-nav-env"], input[name="main-nav-env-offcanvas"]', function () {
		if (!this.checked) {
			return;
		}
		const isLive = this.id.endsWith('live');
		if (this.name === 'main-nav-env') {
			document.getElementById(isLive ? 'offcanvas-main-nav-env-live' : 'offcanvas-main-nav-env-sandbox').checked = true;
		} else {
			document.getElementById(isLive ? 'main-nav-env-live' : 'main-nav-env-sandbox').checked = true;
		}
	});
}

function initMainNavNotifications($) {
	const $menu = $('.app-main-nav-notifications-menu');
	if (!$menu.length) {
		return;
	}

	function syncUnreadUi() {
		const unread = $menu.find('.app-main-nav-notification-entry:not(.is-read)').length;
		$('.app-main-nav-notifications-dot').toggleClass('is-hidden', unread === 0);
		$menu.toggleClass('app-main-nav-notifications-menu--all-read', unread === 0);
	}

	$menu.on('click', '.app-main-nav-notifications-mark-all-read', function (e) {
		e.preventDefault();
		e.stopPropagation();
		$menu.find('.app-main-nav-notification-entry').addClass('is-read');
		syncUnreadUi();
	});

	syncUnreadUi();
}

jQuery(function ($) {
	if (typeof lucide !== 'undefined') {
		lucide.createIcons();
	}

	initMainNavNotifications($);
	initMainNavEnvSync($);

	$('#appSidebarMenu').on('shown.bs.offcanvas', function () {
		if (typeof lucide !== 'undefined') {
			lucide.createIcons();
		}
	});

	if (typeof AirDatepicker !== 'undefined' && document.querySelector('#date')) {
		$('#date').data('airDatepicker', new AirDatepicker('#date', {
			autoClose: true
		}));
	}
});
