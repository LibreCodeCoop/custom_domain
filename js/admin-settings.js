(function () {
	'use strict'

	const root = document.getElementById('custom-domain-settings')
	if (!root) return

	const request = async (url, options) => {
		options = options || {}
		options.headers = Object.assign({'requesttoken': OC.requestToken}, options.headers || {})
		const response = await fetch(url, options)
		const data = await response.json()
		if (!response.ok || data.status === 'error') throw new Error(data.message || 'Request failed')
		return data
	}

	root.querySelectorAll('.custom-domain-upload').forEach((form) => {
		form.addEventListener('submit', async (event) => {
			event.preventDefault()
			const company = form.closest('[data-company-code]').dataset.companyCode
			const key = form.dataset.key
			const data = new FormData(form)
			try {
				await request(OC.generateUrl('/apps/custom_domain/theme/{code}/{key}', {code: company, key: key}), {method: 'POST', body: data})
				window.location.reload()
			} catch (error) {
				OC.dialogs.alert(error.message, t('custom_domain', 'Could not save theme'))
			}
		})
	})

	root.querySelectorAll('.custom-domain-remove').forEach((button) => {
		button.addEventListener('click', async () => {
			const company = button.closest('[data-company-code]').dataset.companyCode
			try {
				await request(OC.generateUrl('/apps/custom_domain/theme/{code}/{key}', {code: company, key: button.dataset.key}), {method: 'DELETE'})
				window.location.reload()
			} catch (error) {
				OC.dialogs.alert(error.message, t('custom_domain', 'Could not remove theme'))
			}
		})
	})
})()
