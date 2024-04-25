({

    currentTheme: '',
    mainHeader:false,
    
    init: function () {
        moduleLoaded("darkmode", this);

        const currentTheme = localStorage.getItem('theme')
        const mainHeader = document.querySelector('.main-header')

        modules.darkmode.currentTheme = currentTheme;
        modules.darkmode.mainHeader = mainHeader;

        

        if (currentTheme) {
            if (currentTheme === 'dark') {
            if (!document.body.classList.contains('dark-mode')) {
                document.body.classList.add("dark-mode");
            }
            if (mainHeader.classList.contains('navbar-light')) {
                mainHeader.classList.add('navbar-dark');
                mainHeader.classList.remove('navbar-light');
            }
            }
        }

            $(`
            <li class="nav-item dropdown">
                <span class="nav-link pointer" data-toggle="dropdown">
                    <i class="fas fa-lg fa-fw fa-moon"></i>
                </span>
                <div class="dropdown-menu">
                    <a class="dropdown-item theme-select" href="#" id="dark-theme-select">${i18n('darkmode.dark')}</a>
                    <a class="dropdown-item theme-select" href="#" id="light-theme-select">${i18n('darkmode.light')}</a>
                </div>
            </li>
            `).insertAfter("#rightTopDynamic");

            $('.theme-select').on("click", modules.darkmode.switchTheme)

    },

    switchTheme(e) {
        e.preventDefault();
        const id = $(e.target).attr("id");
        if (id === 'dark-theme-select') {
          if (!document.body.classList.contains('dark-mode')) {
            document.body.classList.add("dark-mode");
          }
          if (modules.darkmode.mainHeader.classList.contains('navbar-light')) {
            modules.darkmode.mainHeader.classList.add('navbar-dark');
            modules.darkmode.mainHeader.classList.remove('navbar-light');
          }
          modules.darkmode.currentTheme = 'dark'
          localStorage.setItem('theme', 'dark');
        } else {
          if (document.body.classList.contains('dark-mode')) {
            document.body.classList.remove("dark-mode");
          }
          if (modules.darkmode.mainHeader.classList.contains('navbar-dark')) {
            modules.darkmode.mainHeader.classList.add('navbar-light');
            modules.darkmode.mainHeader.classList.remove('navbar-dark');
          }
          modules.darkmode.currentTheme = 'light'
          localStorage.setItem('theme', 'light');
        }
      }

}).init();