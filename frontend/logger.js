const syncLog = function (message, textColour, bgColour, prefix = "sync") {
    return [
        `%c ${prefix.toUpperCase()} %c ${message}`,
        `font-weight: bold; color: ${textColour}; background: ${bgColour}`,
        "color: inherit"
    ];
}