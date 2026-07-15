{
    showCopyModal: false,
    showCopyCategoryModal: false,
    showCopyTaskModal: false,
    showSaveTemplateModal: false,
    contextMenuOpen: false,
    contextMenuX: 0,
    contextMenuY: 0,
    contextMenuType: null,
    contextMenuId: null,
    contextMenuCanUpdate: false,
    contextMenuCanDelete: false,
    contextMenuCanCreateTask: false,
    contextMenuCanCreateTemplate: false,
    contextMenuCanUpdateStatus: false,
    openContextMenu(event, payload) {
        this.contextMenuOpen = true;
        this.contextMenuX = event.clientX;
        this.contextMenuY = event.clientY;
        this.contextMenuType = payload.type;
        this.contextMenuId = payload.id;
        this.contextMenuCanUpdate = !!payload.canUpdate;
        this.contextMenuCanDelete = !!payload.canDelete;
        this.contextMenuCanCreateTask = !!payload.canCreateTask;
        this.contextMenuCanCreateTemplate = !!payload.canCreateTemplate;
        this.contextMenuCanUpdateStatus = !!payload.canUpdateStatus;
    },
    closeContextMenu() {
        this.contextMenuOpen = false;
        this.contextMenuType = null;
        this.contextMenuId = null;
    },
    buildMenuState(menuHeight, offset = 4) {
        return {
            open: false,
            menuStyle: '',
            toggleMenu(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const right = window.innerWidth - rect.right;

                if (spaceBelow < menuHeight) {
                    this.menuStyle = 'bottom: ' + (window.innerHeight - rect.top + offset) + 'px; right: ' + right + 'px;';
                } else {
                    this.menuStyle = 'top: ' + (rect.bottom + offset) + 'px; right: ' + right + 'px;';
                }

                this.open = !this.open;
            },
            closeMenu() {
                this.open = false;
            },
        };
    },
}
