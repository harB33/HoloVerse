(() => {
  const main = document.querySelector('.profile-settings-main');
  if (!(main instanceof HTMLElement)) {
    return;
  }

  const talentAvatar = main.querySelector('#talent-avatar');
  const talentOptions = Array.from(main.querySelectorAll('[data-talent-id]'));
  const avatarImages = Array.from(main.querySelectorAll('[data-profile-avatar]'));
  const selectedTalentName = main.querySelector('[data-selected-talent-name]');
  const bannerTalentName = main.querySelector('[data-profile-talent-name]');
  const bannerFanbase = main.querySelector('[data-profile-fanbase]');
  const avatarPagination = main.querySelector('.profile-avatar-pagination');
  const previousAvatarPage = main.querySelector('[data-avatar-page-previous]');
  const nextAvatarPage = main.querySelector('[data-avatar-page-next]');
  const avatarPageStatus = main.querySelector('[data-avatar-page-status]');
  const avatarsPerPage = 14;
  const avatarPageCount = Math.ceil(talentOptions.length / avatarsPerPage);
  let avatarPage = 0;
  const renderAvatarPage = () => {
    talentOptions.forEach((option, index) => {
      option.hidden = Math.floor(index / avatarsPerPage) !== avatarPage;
    });
    if (avatarPageStatus instanceof HTMLElement) {
      avatarPageStatus.textContent = `Page ${avatarPage + 1} of ${avatarPageCount}`;
    }
    if (previousAvatarPage instanceof HTMLButtonElement) {
      previousAvatarPage.disabled = avatarPage === 0;
    }
    if (nextAvatarPage instanceof HTMLButtonElement) {
      nextAvatarPage.disabled = avatarPage >= avatarPageCount - 1;
    }
  };
  if (avatarPagination instanceof HTMLElement) {
    avatarPagination.hidden = talentOptions.length <= avatarsPerPage;
    previousAvatarPage?.addEventListener('click', () => {
      if (avatarPage > 0) {
        avatarPage -= 1;
        renderAvatarPage();
      }
    });
    nextAvatarPage?.addEventListener('click', () => {
      if (avatarPage < avatarPageCount - 1) {
        avatarPage += 1;
        renderAvatarPage();
      }
    });
  }
  const currentTalentIndex = talentOptions.findIndex(
    (option) => option.dataset.talentId === talentAvatar?.value
  );
  if (currentTalentIndex >= 0) {
    avatarPage = Math.floor(currentTalentIndex / avatarsPerPage);
  }
  renderAvatarPage();
  let profileColorRequest = 0;
  const normalizeColor = (red, green, blue) => {
    const maximum = Math.max(red, green, blue);
    const minimum = Math.min(red, green, blue);
    const saturation = maximum === 0 ? 0 : (maximum - minimum) / maximum;
    return { red, green, blue, saturation, brightness: maximum / 255 };
  };
  const fallbackColor = (name) => {
    const hue = Array.from(name).reduce(
      (value, character) => (value * 31 + character.codePointAt(0)) % 360,
      0
    );
    const chroma = 0.62;
    const segment = hue / 60;
    const secondary = chroma * (1 - Math.abs((segment % 2) - 1));
    const channels = segment < 1 ? [chroma, secondary, 0]
      : segment < 2 ? [secondary, chroma, 0]
        : segment < 3 ? [0, chroma, secondary]
          : segment < 4 ? [0, secondary, chroma]
            : segment < 5 ? [secondary, 0, chroma]
              : [chroma, 0, secondary];
    return channels.map((channel) => Math.round((channel + 0.25) * 255));
  };
  const applyProfileColor = (imageUrl, name) => {
    const requestId = ++profileColorRequest;
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => {
      if (requestId !== profileColorRequest) {
        return;
      }
      try {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });
        if (!context) {
          throw new Error('Canvas color sampling is unavailable.');
        }
        canvas.width = 32;
        canvas.height = 32;
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        let red = 0;
        let green = 0;
        let blue = 0;
        let count = 0;

        for (let index = 0; index < pixels.length; index += 4) {
          if (pixels[index + 3] < 128) {
            continue;
          }
          const sample = normalizeColor(pixels[index], pixels[index + 1], pixels[index + 2]);
          if (sample.saturation < 0.18 || sample.brightness < 0.16 || sample.brightness > 0.95) {
            continue;
          }
          red += sample.red;
          green += sample.green;
          blue += sample.blue;
          count += 1;
        }

        if (count === 0) {
          throw new Error('The selected talent portrait has no suitable color samples.');
        }
        setProfileAccent(
          [red / count, green / count, blue / count].map((channel) => Math.round(channel))
        );
      } catch (error) {
        if (requestId !== profileColorRequest) {
          return;
        }
        console.info('Using a generated profile color because the talent portrait could not be sampled.', error);
        setProfileAccent(fallbackColor(name));
      }
    };
    image.onerror = () => {
      if (requestId === profileColorRequest) {
        setProfileAccent(fallbackColor(name));
      }
    };
    image.src = imageUrl;
  };
  const setProfileAccent = ([red, green, blue]) => {
    main.style.setProperty('--profile-accent', `rgb(${red} ${green} ${blue})`);
  };
  if (talentAvatar instanceof HTMLInputElement && talentOptions.length > 0) {
    talentOptions.forEach((option) => {
      option.addEventListener('click', () => {
        const { talentId, image, name, fanbase } = option.dataset;
        if (!talentId || !image || !name) {
          return;
        }

        talentAvatar.value = talentId;
        talentOptions.forEach((talentOption) => {
          const selected = talentOption === option;
          talentOption.classList.toggle('is-selected', selected);
          talentOption.setAttribute('aria-pressed', String(selected));
        });
        avatarImages.forEach((avatar) => avatar.setAttribute('src', image));
        if (selectedTalentName instanceof HTMLElement) {
          selectedTalentName.textContent = name;
        }
        if (bannerTalentName instanceof HTMLElement) {
          bannerTalentName.textContent = fanbase || '';
        }
        if (bannerFanbase instanceof HTMLElement) {
          bannerFanbase.hidden = !fanbase;
        }
        applyProfileColor(image, name);
      });
    });

    const selectedOption = talentOptions.find(
      (option) => option.dataset.talentId === talentAvatar.value
    );
    if (selectedOption?.dataset.image && selectedOption.dataset.name) {
      applyProfileColor(selectedOption.dataset.image, selectedOption.dataset.name);
    }
  }

  const addressDialog = main.querySelector('.profile-address-dialog');
  const openAddressDialog = main.querySelector('[data-address-dialog-open]');
  const closeAddressDialog = main.querySelector('[data-address-dialog-close]');
  if (
    addressDialog instanceof HTMLDialogElement
    && openAddressDialog instanceof HTMLButtonElement
  ) {
    const showAddressDialog = () => {
      addressDialog.showModal();
      addressDialog.querySelector('input:not([type="hidden"])')?.focus();
    };
    openAddressDialog.addEventListener('click', showAddressDialog);
    closeAddressDialog?.addEventListener('click', () => addressDialog.close());
    addressDialog.addEventListener('click', (event) => {
      if (event.target === addressDialog) {
        addressDialog.close();
      }
    });
    if (addressDialog.dataset.openOnLoad === 'true') {
      showAddressDialog();
    }
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
