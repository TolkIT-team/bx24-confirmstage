/**
 * Отправляет GET-запрос по URL стадии и показывает уведомления
 * @param {Object} stageData - объект с настройками стадии
 * @param {number|string} entityId - ID текущего CRM-элемента
 * @param {number|string} entityTypeId - ENTITY_ID текущего CRM-элемента
 */
function sendStageRequest(stageData, entityId, entityTypeId) {
	if (!stageData.URL || stageData.URL.length < 1) return;

	const url = new URL(stageData.URL, window.location.origin);
	url.searchParams.set('ID', entityId);
	url.searchParams.set('ENTITY_ID', entityTypeId);

	BX.ajax.get(url.toString(), '', (response) => {
		try {
			const data = JSON.parse(response);
			if (data?.success) {
				if (stageData.SUCCESS_MESSAGE)
					BX.UI.Notification.Center.notify({
						content: stageData.SUCCESS_MESSAGE,
						autoHideDelay: 3000,
					});
			} else {
				if (stageData.ERROR_MESSAGE)
					BX.UI.Notification.Center.notify({
						content: stageData.ERROR_MESSAGE,
						autoHideDelay: 4000,
						color: 'danger',
					});
			}
		} catch (e) {
			if (stageData.SUCCESS_MESSAGE)
				BX.UI.Notification.Center.notify({
					content: stageData.ERROR_MESSAGE,
					autoHideDelay: 3000,
				});
		}
	});
}

BX.ready(() => {
	const originalOnStageChange =
		BX.Crm?.ItemDetailsComponent?.prototype?.onStageChange;
	if (!originalOnStageChange) return;

	BX.Crm.ItemDetailsComponent.prototype.onStageChange = function (...args) {
		const config = window.ConfirmStageConfig;
		if (!config) return originalOnStageChange.apply(this, args);

		const stage = args[0];
		if (!stage) return originalOnStageChange.apply(this, args);

		let stageData = Object.values(config).filter((data) => data.ID == stage.id);
		if (stageData.length > 0) stageData = stageData[0];
		else return originalOnStageChange.apply(this, args);

		if (!stageData.MESSAGE || stageData.MESSAGE.length < 1)
			return originalOnStageChange.apply(this, args);

		const confirmArgs = stageData.DIALOG_TITLE
			? [stageData.MESSAGE, stageData.DIALOG_TITLE]
			: [stageData.MESSAGE];

		const messageBox = BX.UI.Dialogs.MessageBox.confirm(
			...confirmArgs,
			() => {
				const entityId = this.id || null;
				const entityTypeId = this.entityTypeId || null;
				sendStageRequest(stageData, entityId, entityTypeId);

				if (
					!stageData.IS_DISABLE_CHANGE ||
					stageData.IS_DISABLE_CHANGE !== 'on'
				) {
					originalOnStageChange.apply(this, args);
				}

				messageBox.close();
			},
			() => {
				messageBox.close();
			},
		);
	};
});

