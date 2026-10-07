// @ts-check
/** @import { Translations } from "./translation.js" */

import Api from "./api.js";
import Translation from "./translation.js";
import { getAttributeAsNumber, selectElement, selectHTMLTemplateElement } from "./dom.js";
import { CommandStatusDto } from "./Dto/CommandStatusDto.js";
import { HttpPolling } from "./services/HttpPolling.js";
import { disableButton, enableButton, formatDate } from "./utils.js";

const COMMANDS_TABLE_COL_TYPE_ID = "command-type";
const COMMANDS_TABLE_COL_ISSUED_AT_ID = "command-issued-at";
const COMMANDS_TABLE_COL_PROCESSED_AT_ID = "command-processed-at";
const COMMANDS_TABLE_COL_TIMEOUT_AT_ID = "command-timeout-at";
const COMMANDS_TABLE_COL_STATUS_ID = "command-status";

const ACTIVE_BUTTON_CLASS = "btn-primary";
const INACTIVE_BUTTON_CLASS = "btn-secondary";

const UNLOCK_DEVICE_BUTTON_SELECTOR = '[data-action-name="unlockDevice"]';
const COMMANDS_PAGINATION_BUTTON_TEMPLATE_SELECTOR = "#command-pagination-button-template";
const COMMANDS_TABLE_ROW_SELECTOR = "tr[data-command-id]";
const COMMANDS_TABLE_ROW_TEMPLATE_SELECTOR = "#command-row-template";
const COMMANDS_PAGINATION_WRAPPER_SELECTOR = "#command-pagination-wrapper";
const COMMANDS_PAGINATION_BUTTON_SELECTOR = "button[data-pagination-page]";
const COMMANDS_TABLE_BODY_SELECTOR = "#commands-table tbody";
const COMMANDS_HISTORY_PERIOD_BUTTON_SELECTOR = "button[data-command-period]";

const DEFAULT_PAGE_NUM = 1;
const DEFAULT_LIMIT_PER_PAGE = 20;
const DEFAULT_PERIOD = null;

/**
 * @param { number } timeout
 * @returns
 */
export async function sleep(timeout) {
    return new Promise((resolve) => setTimeout(resolve, timeout));
}

/**
 * @param { number } deviceId
 */
export async function accessDeviceDetail(deviceId) {
    let polling = new HttpPolling(() => syncCommandsHistory(deviceId, DEFAULT_PAGE_NUM, DEFAULT_PERIOD, DEFAULT_LIMIT_PER_PAGE));
    polling.start();

    document.addEventListener("click", async (ev) => {
        const target = ev.target;
        const paginationButton = target instanceof Element ? target.closest(COMMANDS_PAGINATION_BUTTON_SELECTOR) : null;
        const unlockDeviceButtonElement = target instanceof Element ? target.closest(UNLOCK_DEVICE_BUTTON_SELECTOR) : null;
        const periodButton = target instanceof Element ? target.closest(COMMANDS_HISTORY_PERIOD_BUTTON_SELECTOR) : null;
        if (paginationButton instanceof HTMLButtonElement) {
            const paginationButtons = getPaginationButtons();
            const rows = getRows();
            const isActive = isButtonActive(paginationButton);

            if (isActive === true) return;

            polling.stop();

            rows.forEach((row) => row.remove());
            paginationButtons.forEach((button) => setButtonActive(button, false));
            setButtonActive(paginationButton, true);

            polling.handler = () => syncCommandsHistory(deviceId, getActivePageNum(), getActivePeriod(), DEFAULT_LIMIT_PER_PAGE);
            polling.start();
        }

        if (unlockDeviceButtonElement instanceof HTMLButtonElement) {
            try {
                disableButton(unlockDeviceButtonElement);
                await Api.unlockDevice(deviceId);
            } finally {
                enableButton(unlockDeviceButtonElement);
            }
        }

        if (periodButton instanceof Element) {
            const periodButtons = getPeriodButtons();
            const rows = getRows();
            const paginationButtons = getPaginationButtons();
            const clickedPeriod = getPeriod(periodButton);
            const currentPeriod = getActivePeriod();
            polling.stop();

            periodButtons.forEach((button) => setButtonActive(button, false));
            if (clickedPeriod !== currentPeriod) {
                setButtonActive(periodButton, true);
            }
            paginationButtons.forEach((button) => button.remove());
            rows.forEach((row) => row.remove());

            polling.handler = () => syncCommandsHistory(deviceId, getActivePageNum(), getActivePeriod(), DEFAULT_LIMIT_PER_PAGE);
            polling.start();
        }
    });
}

/**
 * @param { number } deviceId
 * @param { number } pageNum
 * @param { number | null } period
 * @param { number } limitPerPage
 */
export async function syncCommandsHistory(deviceId, pageNum, period, limitPerPage) {
    const paginationButtonsWrapper = getPaginationButtonsWrapper();
    const paginationButtonTemplate = getPaginationButtonTemplate();
    const paginationButtons = getPaginationButtons();
    const rowTemplate = getRowTemplate();
    const tableBody = getTableBody();
    const rows = getRows();

    const result = await Api.getCommandsStatus(deviceId, pageNum, period, new AbortController());
    const pageCount = result.pageCount;
    const commands = result.commands.reverse();

    let newRows = [];
    for (const command of commands.slice(0, limitPerPage)) {
        let row = findRowByCommandId(command.id);
        if (row === null) {
            const clone = /** @type { DocumentFragment } **/ (rowTemplate.content.cloneNode(true));
            row = selectElement(clone, "tr[data-command-id]");
            newRows.push(row);
        }

        updateRow(row, command);
    }

    if (newRows.length > 0) {
        const countToRemove = rows.length + newRows.length - limitPerPage;
        const rowsToRemove = countToRemove > 0 ? rows.slice(countToRemove * -1) : [];
        rowsToRemove.forEach((row) => row.remove());
        newRows.forEach((row) => tableBody.insertAdjacentElement("afterbegin", row));
    }

    if (paginationButtons.length !== pageCount) {
        paginationButtons.forEach((button) => button.remove());
        for (let i = 0; i < pageCount; i++) {
            const clone = /** @type { DocumentFragment } */ (paginationButtonTemplate.content.cloneNode(true));
            const currentPageNum = i + 1;
            const paginationButton = selectElement(clone, COMMANDS_PAGINATION_BUTTON_SELECTOR);
            const isActive = pageNum === currentPageNum;

            setButtonActive(paginationButton, isActive);
            setPageNum(paginationButton, currentPageNum);

            paginationButtonsWrapper.appendChild(paginationButton);
        }
    }

    paginationButtons.forEach((button) => {
        const isActive = getPageNum(button) === pageNum;
        setButtonActive(button, isActive);
    });
}

/**
 * @param { number } id
 * @returns { Element | null }
 */
function findRowByCommandId(id) {
    return document.querySelector(`[data-command-id="${id.toString()}"`);
}

function getActivePageNum() {
    const paginationButtons = getPaginationButtons();
    const activeButton = paginationButtons.find((button) => isButtonActive(button));
    return activeButton === undefined ? DEFAULT_PAGE_NUM : getPageNum(activeButton);
}

function getActivePeriod() {
    const periodButtons = getPeriodButtons();
    const activeButton = periodButtons.find((button) => isButtonActive(button));
    return activeButton === undefined ? DEFAULT_PERIOD : getPeriod(activeButton);
}

/**
 * @param { Element } element
 * @returns
 */
function getPeriod(element) {
    return getAttributeAsNumber(element, "data-command-period");
}

/**
 * @param { number } page
 * @returns { Element }
 */
function getPaginationButtonByPage(page) {
    return selectElement(document, `[data-command-page="${page.toString()}"]`);
}

/**
 * @param { Element } element
 * @param { number } page
 */
function setPageNum(element, page) {
    element.setAttribute("data-pagination-page", page.toString());
    element.textContent = page.toString();
}

/**
 * @param { Element } element
 */
function getPageNum(element) {
    return getAttributeAsNumber(element, "data-pagination-page");
}

function getPeriodButtons() {
    return Array.from(document.querySelectorAll(COMMANDS_HISTORY_PERIOD_BUTTON_SELECTOR));
}

/**
 * @param { Element } element
 * @param { boolean } isActive
 */
function setButtonActive(element, isActive) {
    isActive ? element.classList.replace(INACTIVE_BUTTON_CLASS, ACTIVE_BUTTON_CLASS) : element.classList.replace(ACTIVE_BUTTON_CLASS, INACTIVE_BUTTON_CLASS);
}

/**
 * @param { Element } element
 * @returns { boolean }
 */
function isButtonActive(element) {
    return element.classList.contains(ACTIVE_BUTTON_CLASS);
}

function getPaginationButtonTemplate() {
    return selectHTMLTemplateElement(document, COMMANDS_PAGINATION_BUTTON_TEMPLATE_SELECTOR);
}

/**
 * @param { Element } row
 * @param { CommandStatusDto } command
 */
function updateRow(row, command) {
    const translations = Translation.load();
    const typeCell = getRowCellByColumnId(row, COMMANDS_TABLE_COL_TYPE_ID);
    const issuedAtCell = getRowCellByColumnId(row, COMMANDS_TABLE_COL_ISSUED_AT_ID);
    const processedAtCell = getRowCellByColumnId(row, COMMANDS_TABLE_COL_PROCESSED_AT_ID);
    const timeoutAtCell = getRowCellByColumnId(row, COMMANDS_TABLE_COL_TIMEOUT_AT_ID);
    const statusCell = getRowCellByColumnId(row, COMMANDS_TABLE_COL_STATUS_ID);
    const statusBadge = selectElement(statusCell, ".badge");

    updateCommandId(row, command.id);

    typeCell.textContent = Translation.getCommandType(translations, command.type);
    issuedAtCell.textContent = command.issuedAtDate !== null ? formatDate(command.issuedAtDate) : "N/A";
    processedAtCell.textContent = command.processedAtDate !== null ? formatDate(command.processedAtDate) : "N/A";
    timeoutAtCell.textContent = command.timeoutAtDate !== null ? formatDate(command.timeoutAtDate) : "N/A";
    updateCommandStatusBadge(statusBadge, translations, command.status);
}

/**
 * @param { Element } element
 * @param { number } id
 */
function updateCommandId(element, id) {
    element.setAttribute("data-command-id", id.toString());
}

/**
 * @param { Element } element
 * @param { Translations } translations
 * @param { string } status
 */
function updateCommandStatusBadge(element, translations, status) {
    switch (status) {
        case "in_progress":
            element.classList.add("badge-warning");
            break;
        case "timeout":
            element.classList.add("badge-danger");
            break;
        case "processed":
            element.classList.add("badge-success");
            break;
    }

    element.textContent = Translation.getCommandStatus(translations, status);
}

/**
 * @param { Element } row
 * @param { string } id
 */
function getRowCellByColumnId(row, id) {
    return selectElement(row, `[data-col-id="${id}"]`);
}

function getTableBody() {
    return selectElement(document, COMMANDS_TABLE_BODY_SELECTOR);
}

function getPaginationButtonsWrapper() {
    return selectElement(document, COMMANDS_PAGINATION_WRAPPER_SELECTOR);
}

/**
 * @returns { Element[] }
 */
function getRows() {
    return Array.from(document.querySelectorAll(COMMANDS_TABLE_ROW_SELECTOR));
}

function getRowTemplate() {
    return selectHTMLTemplateElement(document, COMMANDS_TABLE_ROW_TEMPLATE_SELECTOR);
}

function getPaginationButtons() {
    const elements = Array.from(document.querySelectorAll(COMMANDS_PAGINATION_BUTTON_SELECTOR));
    if (!elements.every((element) => element instanceof HTMLButtonElement)) {
        throw new Error(`Some pagination buttons elements found using selector "${COMMANDS_PAGINATION_BUTTON_SELECTOR}" are not instances of HTMLButtonElement`);
    }

    return elements;
}
