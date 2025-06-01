import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Box, Button, TextField, Typography, Paper } from '@mui/material';
import axios from 'axios';

const ArtisanConsole = () => {
    const [command, setCommand] = useState('');
    const [output, setOutput] = useState('');
    const [loading, setLoading] = useState(false);

    const runCommand = async () => {
        setLoading(true);
        setOutput('');
        try {
            const res = await axios.post('/api/run-artisan', { command });
            setOutput(res.data.output || res.data.error);
        } catch (err) {
            setOutput('Failed to run command');
        }
        setLoading(false);
    };

    return (
        <Box>
            <Typography variant="h5" gutterBottom>Laravel Artisan Console</Typography>

            <TextField
                fullWidth
                label="Artisan Command"
                placeholder="e.g. migrate, queue:work, config:cache"
                value={command}
                onChange={(e) => setCommand(e.target.value)}
                sx={{ mb: 2 }}
            />

            <Button variant="contained" onClick={runCommand} disabled={loading}>
                {loading ? 'Running...' : 'Run'}
            </Button>

            {output && (
                <Paper elevation={3} sx={{ mt: 3, p: 2, whiteSpace: 'pre-wrap', background: '#111', color: '#0f0' }}>
                    {output}
                </Paper>
            )}
        </Box>
    );
};

ArtisanConsole.layout = (page) => <MainLayout>{page}</MainLayout>;

export default ArtisanConsole;
