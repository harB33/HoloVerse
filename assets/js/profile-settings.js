(() => {
  const main = document.querySelector('.profile-settings-main');
  if (!(main instanceof HTMLElement)) {
    return;
  }

  const navigation = main.querySelector('.profile-settings-tabs');
  const tabs = Array.from(main.querySelectorAll('[data-settings-tab]'));
  const panels = Array.from(main.querySelectorAll('[data-settings-panel]'));
  if (!(navigation instanceof HTMLElement) || tabs.length === 0 || panels.length === 0) {
    return;
  }

  const panelsByName = new Map(
    panels.map((panel) => [panel.dataset.settingsPanel, panel])
  );
  const tabsByName = new Map(
    tabs.map((tab) => [tab.dataset.settingsTab, tab])
  );
  const validNames = new Set(
    tabs
      .map((tab) => tab.dataset.settingsTab)
      .filter((name) => typeof name === 'string')
  );

  const hashName = () => {
    const panelId = window.location.hash.slice(1);
    const panel = document.getElementById(panelId);
    return panel instanceof HTMLElement ? panel.dataset.settingsPanel : null;
  };

  const activate = (name) => {
    if (!validNames.has(name)) {
      return;
    }

    tabs.forEach((tab) => {
      const selected = tab.dataset.settingsTab === name;
      tab.classList.toggle('is-active', selected);
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
    });

    panels.forEach((panel) => {
      const selected = panel.dataset.settingsPanel === name;
      const tab = tabsByName.get(panel.dataset.settingsPanel);
      panel.hidden = !selected;
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('tabindex', '0');
      if (tab instanceof HTMLElement) {
        panel.setAttribute('aria-labelledby', tab.id);
      }
    });
  };

  navigation.setAttribute('role', 'tablist');
  const mobileNavigation = window.matchMedia('(max-width: 760px)');
  const updateOrientation = () => {
    navigation.setAttribute(
      'aria-orientation',
      mobileNavigation.matches ? 'horizontal' : 'vertical'
    );
  };
  updateOrientation();
  mobileNavigation.addEventListener('change', updateOrientation);
  tabs.forEach((tab) => {
    const name = tab.dataset.settingsTab;
    const panel = typeof name === 'string' ? panelsByName.get(name) : null;
    if (!(panel instanceof HTMLElement)) {
      return;
    }

    tab.id = `${name}-settings-tab`;
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-controls', panel.id);
  });
  main.dataset.tabsReady = 'true';

  const initialName = hashName() || main.dataset.initialTab;
  activate(validNames.has(initialName) ? initialName : tabs[0].dataset.settingsTab);

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => {
      const name = tab.dataset.settingsTab;
      if (typeof name !== 'string' || !validNames.has(name)) {
        return;
      }

      event.preventDefault();
      window.history.pushState(null, '', tab.hash);
      activate(name);
    });

    tab.addEventListener('keydown', (event) => {
      let nextIndex = index;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        nextIndex = (index + 1) % tabs.length;
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        nextIndex = (index - 1 + tabs.length) % tabs.length;
      } else if (event.key === 'Home') {
        nextIndex = 0;
      } else if (event.key === 'End') {
        nextIndex = tabs.length - 1;
      } else {
        return;
      }

      event.preventDefault();
      tabs[nextIndex].focus();
      tabs[nextIndex].click();
    });
  });

  window.addEventListener('popstate', () => {
    const name = hashName();
    if (name) {
      activate(name);
    }
  });
})();
