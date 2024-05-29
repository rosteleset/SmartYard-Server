({
    currentTheme: '',
    mainHeader: null,
    mediaQuery: null,

    // Инициализация модуля
    init() {
        moduleLoaded("darkmode", this);

        // Загрузка текущей темы из локального хранилища
        this.currentTheme = lStore('theme');
        this.mainHeader = document.querySelector('.main-header');

        // Применение текущей темы
        this.applyTheme(this.currentTheme);

        // Создание выпадающего меню для выбора темы
        this.createThemeDropdown();

        // Привязка событий к элементам выбора темы
        this.attachEventListeners();
    },

    // Создание выпадающего меню для выбора темы
    createThemeDropdown() {
        $(`
            <li class="nav-item dropdown">
                <span class="nav-link pointer" data-toggle="dropdown">
                    <i class="fas fa-lg fa-fw fa-moon"></i>
                </span>
                <div class="dropdown-menu">
                    <a class="dropdown-item theme-select ${!this.currentTheme || this.currentTheme === 'auto' ? 'active' : ''}" href="#" data-theme="auto">${i18n('darkmode.auto')}</a>
                    <a class="dropdown-item theme-select ${this.currentTheme === 'dark' ? 'active' : ''}" href="#" data-theme="dark">${i18n('darkmode.dark')}</a>
                    <a class="dropdown-item theme-select ${this.currentTheme === 'light' ? 'active' : ''}" href="#" data-theme="light">${i18n('darkmode.light')}</a>
                </div>
            </li>
        `).insertAfter("#rightTopDynamic");
    },

    // Привязка событий к элементам выбора темы
    attachEventListeners() {
        $('.theme-select').on("click", (e) => this.switchTheme(e));
    },

    // Применение выбранной темы
    applyTheme(theme) {
        switch (theme) {
            case 'dark':
                this.setDark();
                break;
            case 'light':
                this.setLight();
                break;
            case 'auto':
            default:
                this.setAuto();
                break;
        }
    },

    // Установка темной темы
    setDark(updateStore = true) {
        document.body.classList.add("dark-mode");
        this.updateNavbar('dark');

        if (updateStore) {
            this.currentTheme = 'dark';
            lStore('theme', this.currentTheme);
        }
    },

    // Установка светлой темы
    setLight(updateStore = true) {
        document.body.classList.remove("dark-mode");
        this.updateNavbar('light');

        if (updateStore) {
            this.currentTheme = 'light';
            lStore('theme', this.currentTheme);
        }
    },

    // Обновление темы в зависимости от системных настроек
    updateMedia(media) {
        if (media.matches) {
            this.setDark(false);
        } else {
            this.setLight(false);
        }
    },

    // Автоматическое применение темы в зависимости от системных настроек
    setAuto() {
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        this.updateMedia(this.mediaQuery);
        this.mediaQuery.onchange = (e) => this.updateMedia(e);

        this.currentTheme = 'auto';
        lStore('theme', this.currentTheme);
    },

    // Обновление классов навигационного меню в зависимости от темы
    updateNavbar(theme) {
        if (theme === 'dark') {
            this.mainHeader.classList.add('navbar-dark');
            this.mainHeader.classList.remove('navbar-light');
        } else {
            this.mainHeader.classList.add('navbar-light');
            this.mainHeader.classList.remove('navbar-dark');
        }
    },

    // Переключение темы
    switchTheme(e) {
        e.preventDefault();
        $('.theme-select').removeClass('active');
        $(e.target).addClass('active');
        const theme = $(e.target).data("theme");

        // Отключение слушателя изменений темы при ручном выборе
        if (this.mediaQuery && theme && theme !== 'auto') {
            this.mediaQuery.onchange = null;
        }

        this.applyTheme(theme);
    }
}).init();
