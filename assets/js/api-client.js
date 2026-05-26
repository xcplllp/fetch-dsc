/**
 * Standalone DSC Registry API Client
 * Coordinates Fetch operations with the backend api.php controller
 */
const API = {
    /**
     * Get list of all registered DSC records and statistics
     */
    async getDSCList() {
        try {
            const response = await fetch('api.php?action=list');
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to load ledger records');
            }
            return result;
        } catch (error) {
            console.error('API Error (getDSCList):', error);
            throw error;
        }
    },

    /**
     * Auto-detect and register/update a physical USB DSC Token
     * @param {object} hardwareData - { holder_name, serial_number, expiry_date, dsc_class }
     */
    async registerDSC(hardwareData) {
        try {
            const response = await fetch('api.php?action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(hardwareData)
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to register DSC token');
            }
            return result;
        } catch (error) {
            console.error('API Error (registerDSC):', error);
            throw error;
        }
    },

    /**
     * Update dynamic ledger metadata for a specific row
     * @param {number} id - Record ID to update
     * @param {object} data - { client_name, pin, email, phone, token_status, location, ... }
     */
    async updateDSC(id, data) {
        try {
            const response = await fetch('api.php?action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    ...data
                })
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to update ledger record');
            }
            return result;
        } catch (error) {
            console.error('API Error (updateDSC):', error);
            throw error;
        }
    },

    /**
     * Delete a row from the ledger
     * @param {number} id - Record ID to delete
     */
    async deleteDSC(id) {
        try {
            const response = await fetch('api.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Failed to delete ledger record');
            }
            return result;
        } catch (error) {
            console.error('API Error (deleteDSC):', error);
            throw error;
        }
    }
};
