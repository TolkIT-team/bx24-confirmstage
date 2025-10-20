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

		if (stageData.MESSAGE.length < 1)
			return originalOnStageChange.apply(this, args);

		const messageBox = BX.UI.Dialogs.MessageBox.confirm(
			stageData.MESSAGE,
			'Подтверждение смены стадии',
			() => {
				messageBox.close();
				originalOnStageChange.apply(this, args);
			},
			() => {
				messageBox.close();
			},
		);
	};
});

