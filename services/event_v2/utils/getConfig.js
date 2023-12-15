const config = await import('../config.json', {
    assert: { type: 'json' }
});

export { config }