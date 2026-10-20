"use strict";

document.addEventListener("DOMContentLoaded", () => {
    window.PureViaCancellation?.init(window.PUREVIA_ORDER_DETAILS || {});
    window.PureViaReturns?.init(window.PUREVIA_ORDER_DETAILS || {});
});
