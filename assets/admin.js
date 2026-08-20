(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var printButton = event.target.closest('[data-storevitals-print]');
		if (printButton) {
			event.preventDefault();
			window.print();
			return;
		}

		var confirmLink = event.target.closest('[data-storevitals-confirm]');
		if (confirmLink) {
			var message = confirmLink.getAttribute('data-storevitals-confirm') || '';
			if (message && !window.confirm(message)) {
				event.preventDefault();
			}
		}
	});

	var activeTab = document.querySelector('.storevitals-tab.is-active');
	if (activeTab && typeof activeTab.scrollIntoView === 'function') {
		activeTab.scrollIntoView({block: 'nearest', inline: 'nearest'});
	}
}());
