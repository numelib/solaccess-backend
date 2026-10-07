// @ts-check

/**
 * @typedef { { querySelector: (arg0: string) => Element | null } } QuerySelector
 */

/**
 * @returns { HTMLElement }
 */
export function createBootstrapSpinner() {
    const div = document.createElement("div");
    const span = document.createElement("span");
    div.classList.add("spinner-border");
    div.role = "status";

    span.classList.add("visually-hidden");
    span.textContent = "Loading ...";

    div.appendChild(span);

    return div;
}

/**
 * @param { string } fill
 * @param { number } size
 * @returns { SVGSVGElement }
 */
export function createUnlockIcon(fill, size = 16) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", size.toString());
    svg.setAttribute("height", size.toString());
    svg.setAttribute("fill", fill);
    svg.setAttribute("viewBox", "0 0 16 16");
    svg.setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xlink", "http://www.w3.org/1999/xlink");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("fill-rule", "evenodd");
    path.setAttribute("d", "M12 0a4 4 0 0 1 4 4v2.5h-1V4a3 3 0 1 0-6 0v2h.5A2.5 2.5 0 0 1 12 8.5v5A2.5 2.5 0 0 1 9.5 16h-7A2.5 2.5 0 0 1 0 13.5v-5A2.5 2.5 0 0 1 2.5 6H8V4a4 4 0 0 1 4-4M2.5 7A1.5 1.5 0 0 0 1 8.5v5A1.5 1.5 0 0 0 2.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 9.5 7z");

    svg.appendChild(path);

    return svg;
}

/**
 * @param { string } fill
 * @param { number } size
 * @returns { SVGSVGElement }
 */
export function createLockIcon(fill, size = 16) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", size.toString());
    svg.setAttribute("height", size.toString());
    svg.setAttribute("fill", fill);
    svg.setAttribute("viewBox", "0 0 16 16");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("fill-rule", "evenodd");
    path.setAttribute("d", "M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3");

    svg.appendChild(path);

    return svg;
}

/**
 * @param { QuerySelector } parent
 * @param { string } selector
 * @returns { Element }
 */
export function selectElement(parent, selector) {
    const element = parent.querySelector(selector);
    if (element === null) {
        throw new Error(`Element with selector "${selector}" is null`);
    }

    return element;
}

/**
 * @param { QuerySelector } parent
 * @param { string } selector
 * @returns { HTMLButtonElement }
 */
export function selectHTMLButtonElement(parent, selector) {
    const element = selectElement(parent, selector);
    if (!(element instanceof HTMLButtonElement)) {
        throw new Error(`Element with selector "${selector}" is not an instance of HTMLButtonElement`);
    }

    return element;
}

/**
 * @param { QuerySelector } parent
 * @param { string } selector
 * @returns { HTMLTemplateElement }
 */
export function selectHTMLTemplateElement(parent, selector) {
    const element = selectElement(document, selector);
    if (!(element instanceof HTMLTemplateElement)) {
        throw new Error(`Element with selector "${selector}" is not an instance of HTMLTemplateElement`);
    }

    return element;
}

/**
 * @param { Element } element
 * @param { string } attrName
 * @returns { number }
 */
export function getAttributeAsNumber(element, attrName) {
    const attrStr = element.getAttribute(attrName);
    if (attrStr === null) {
        throw new Error(`Element attribute "${attrName}" is null`);
    }

    const attrNum = Number.parseInt(attrStr, 10);
    if (Number.isNaN(attrNum)) {
        throw new Error(`Element attribute "${attrName}" cannot be parsed to an integer`);
    }

    return attrNum;
}
