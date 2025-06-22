/** Класс "ленивой" загрузки */
class Asyncload {
	/**
	 * Предустановка базовых переменных
	 * 
	 * @author Andrey Akiwa <mishin.a@akiwa.ru>
	 * @param {boolean} log выполнять логирование в консоле
	 */
	constructor(log = false) {
		this.logs = log;
		this.l10n = {
			scriptIsNotFunction: 'Параметр "script" не является функцией!',
			linkIsNotUrl: 'Параметр "script" не является ссылкой!',
			elementIsNotDomElement: 'Параметр "element" не является элементом DOM\'а!',
			scriptOnce: 'Скрипт был выполнен один раз.',
			listenersDeleted: 'Удалены все связанные с данным скриптом слушатели на элементе.',
			userFunctionAdded: 'Асинхронный скрипт добавлен.',
			userFunctionExecuted: 'Асинхронный скрипт выполнен.',
		};
	}

	/**
	 * Добавление скрипта
	 * 
	 * @author Andrey Akiwa <mishin.a@akiwa.ru>
	 * @param {function} script функция
	 * @param {object} element элемент DOM'а, к которому привязываются события
	 * @param {array} events список событий, при которых будет добавляться скрипт
	 * @param {object} embed элемент DOM'а, в который будет добавляться скрипт
	 * @param {boolean} once выполнять функцию только один раз
	 */
	add(script, element = window, events = ['pointermove', 'scroll'], embed = document.body, once = true) {
		if (typeof script === 'function') {
			const handlers = {};
			events.forEach((event) => {
				const handler = () => {
					const included = this._embed(script, embed);
					if (this.logs) console.log(this.l10n.userFunctionAdded, included);
					if (once === true) {
						events.forEach((eventInner) => {
							element.removeEventListener(eventInner, handlers[eventInner]);
						});
						if (this.logs) {
							if (once === true) {
								console.log(this.l10n.scriptOnce, this.l10n.listenersDeleted, included);
							} else {
								console.log(this.l10n.listenersDeleted, included);
							}
						}
					}
				};
				handlers[event] = handler;
				element.addEventListener(event, handler);
			});
		} else {
			console.error(this.l10n.scriptIsNotFunction);
		}
	}

	/**
	 * Запуск пользовательского скрипта
	 * 
	 * @author Andrey Akiwa <mishin.a@akiwa.ru>
	 * @param {function} script функция
	 * @param {object} element элемент DOM'а, к которому привязываются события
	 * @param {array} events список событий, при которых будет выполняться скрипт
	 * @param {boolean} once выполнять функцию только один раз
	 */
	run(script, element, events = ['click'], once = false) {
		if (typeof script === 'function') {
			if (typeof element === 'object') {
				const handlers = {};
				events.forEach((event) => {
					const handler = () => {
						script();
						if (this.logs) console.log(this.l10n.userFunctionExecuted, script);
						if (once === true) {
							events.forEach((eventInner) => {
								element.removeEventListener(eventInner, handlers[eventInner]);
							});
							if (this.logs) {
								if (once === true) {
									console.log(this.l10n.scriptOnce, this.l10n.listenersDeleted, script);
								} else {
									console.log(this.l10n.listenersDeleted, script);
								}
							}
						}
					};
					handlers[event] = handler;
					element.addEventListener(event, handler);
				});
			} else {
				console.error(this.l10n.elementIsNotDomElement);
			}
		} else {
			console.error(this.l10n.scriptIsNotFunction);
		}
	}

	/**
	 * Добавление стороннего скрипта по ссылке
	 * 
	 * @author Andrey Akiwa <mishin.a@akiwa.ru>
	 * @param {string} link ссылка на сторонний ресурс
	 * @param {object} element элемент DOM'а, к которому привязываются события
	 * @param {array} events список событий, при которых будет добавляться скрипт
	 * @param {object} embed элемент DOM'а, в который будет добавляться скрипт
	 * @param {boolean} once выполнять функцию только один раз
	 */
	external(link, element = window, events = ['pointermove', 'scroll'], embed = document.body, once = true) {
		if (typeof link === 'string') {
			const handlers = {};
			events.forEach((event) => {
				try {
					const handler = () => {
						const url = new URL(link);
						const included = this._embed(url.href, embed);
						if (this.logs) console.log(this.l10n.userFunctionAdded, included);
						if (once === true) {
							events.forEach((eventInner) => {
								element.removeEventListener(eventInner, handlers[eventInner]);
							});
							if (this.logs) {
								if (once === true) {
									console.log(this.l10n.scriptOnce, this.l10n.listenersDeleted, included);
								} else {
									console.log(this.l10n.listenersDeleted, included);
								}
							}
						}
					};
					handlers[event] = handler;
					element.addEventListener(event, handler);
				} catch (error) {
					console.error(this.l10n.linkIsNotUrl);
				}
			});
		} else {
			console.error(this.l10n.linkIsNotUrl);
		}
	}

	/**
	 * Внедрение скрипта в документ
	 * (внутренний метод)
	 * 
	 * @author Andrey Akiwa <mishin.a@akiwa.ru>
	 * @param {string} script ссылка на сторонний скрипт
	 * @returns созданный script в DOM'е
	 */
	_embed(script, element) {
		const embed = document.createElement('script');
		if (typeof script === 'function') {
			const func = script.toString().match(/\{([\s\S]*)\}/)[1];
			embed.textContent = func.trim();
		} else {
			embed.src = script;
		}
		element.appendChild(embed);
		return embed;
	}
}