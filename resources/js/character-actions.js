export default () => ({
    character: null,
    action: 'unstuck',
    requestId: '',
    submitting: false,

    openAction(character, action) {
        this.character = character;
        this.action = action;
        this.requestId = crypto.randomUUID();
        this.submitting = false;
        this.$dispatch('open-modal', 'character-action');
    },

    get title() {
        return {unstuck: 'Unstuck', rename: 'Rename', customize: 'Change appearance'}[this.action];
    },
});
