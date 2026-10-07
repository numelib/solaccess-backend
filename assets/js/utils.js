/**
 * @returns { number|null }
 */
export function getEntityId() {
    const attr = document.body.getAttribute("data-entity-id");
    if (attr === null) return null;

    const id = Number.parseInt(attr, 10);
    return Number.isNaN(id) ? null : id;
}

/**
 * @returns { 'index' | 'detail' | 'new' | 'edit' | null }
 */
export function getCrudPage() {
    const attr = document.body.getAttribute("data-crud-page");
    return ["index", "detail", "new", "edit"].includes(attr) ? attr : null;
}

/**
 * @param { HTMLButtonElement } button
 */
export function disableButton(button) {
    button.style.filter = "grayscale(1)";
    button.disabled = true;
}

/**
 * @param { HTMLButtonElement } button
 */
export function enableButton(button) {
    button.style.filter = "grayscale(0)";
    button.disabled = false;
}

/**
 * @param { string } key
 * @param { any[] } values
 * @returns { string }
 */
export function queryParamsFromArray(key, values) {
    const params = new URLSearchParams();
    params.append(`${key}[]`, value);
    values.reduce((prev, curr) => {
        prev += `${key}[]=${curr}`;
    });
}

/**
 * @param { Date } date
 * @returns { string }
 */
export function formatDate(date) {
    return date.toLocaleDateString([], {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
}
