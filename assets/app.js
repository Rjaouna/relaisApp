import './stimulus_bootstrap.js';
import './styles/app.css';

const getModalElement = () => document.getElementById('crud-modal');

const getModalInstance = () => {
    const modalElement = getModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const refreshCrudList = async (url) => {
    const target = document.querySelector('[data-crud-list]');

    if (!target || !url) {
        return;
    }

    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to refresh list.');
    }

    target.innerHTML = await response.text();
};

const openCrudModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load form.');
    }

    const modalBody = document.querySelector('[data-crud-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    getModalInstance()?.show();
};

const submitCrudForm = async (form) => {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to save form.');
    }

    const payload = await response.json();
    if (!payload.success) {
        const modalBody = document.querySelector('[data-crud-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
        }

        return;
    }

    getModalInstance()?.hide();
    await refreshCrudList(form.dataset.crudRefreshUrl);
};

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openCrudModal(trigger.dataset.crudModalUrl);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-crud-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitCrudForm(form);
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-delete-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();

    if (!window.confirm(trigger.dataset.crudConfirm ?? 'Confirmer la suppression ?')) {
        return;
    }

    const response = await fetch(trigger.dataset.crudDeleteUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to delete item.');
    }

    await refreshCrudList(trigger.dataset.crudRefreshUrl);
});
