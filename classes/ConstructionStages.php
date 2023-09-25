<?php

class ConstructionStages
{
	private $db;

	public function __construct()
	{
		$this->db = Api::getDb();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getSingle($id)
	{
		$stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		");
		$stmt->execute(['id' => $id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function post(ConstructionStagesCreate $data)
	{
		$validationErrors = $this->validateConstructionStageData((array)$data);


		if (!empty($validationErrors)) {
			return ['error' => 'Validation failed', 'validationErrors' => $validationErrors];
		}

		$duration = $this->calculateDuration($data);


		$data->duration = $duration;

		$stmt = $this->db->prepare("
			INSERT INTO construction_stages
			    (name, start_date, end_date, duration, durationUnit, color, externalId, status)
			    VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
			");

		$stmt->execute([
			'name' => $data->name,
			'start_date' => $data->startDate,
			'end_date' => $data->endDate,
			'duration' => $data->duration,
			'durationUnit' => $data->durationUnit,
			'color' => $data->color,
			'externalId' => $data->externalId,
			'status' => $data->status,
		]);
		return $this->getSingle($this->db->lastInsertId());
	}

	    /**
     * Update a construction stage partially.
     *
     * @param int $id   The ID of the construction stage to update.
     * @param object $data Data containing the fields to update.
     *
     * @return array Associative array representing the updated construction stage.
     */

    public function patch(ConstructionStagesUpdate $data, $id)
    {


        $existingStage = ($this->getSingle($id))[0];
	

        if (!$existingStage) {
            return ['error' => 'Construction stage not found'];
        }

        if (property_exists($data,'name')) {
            $existingStage['name'] = $data->name;
        }
        if (property_exists($data,'startDate')) {
            $existingStage['startDate'] = $data->startDate;
        }
        if (property_exists($data,'endDate')) {
            $existingStage['endDate'] = $data->endDate;
        }
        if (property_exists($data,'duration')) {
            $existingStage['duration'] = $data->duration;
        }
        if (property_exists($data,'durationUnit')) {
            $existingStage['durationUnit'] = $data->durationUnit;
        }
        if (property_exists($data,'color')) {
            $existingStage['color'] = $data->color;
        }
        if (property_exists($data,'externalId')) {
            $existingStage['externalId'] = $data->externalId;
        }

        if (property_exists($data,'status')) {
            $allowedStatuses = ['NEW', 'PLANNED', 'DELETED'];
            if (!in_array($data->status, $allowedStatuses)) {
                return ['error' => 'Invalid status value'];
            }
            $existingStage['status'] = $data->status;
        }
		
		

        $stmt = $this->db->prepare("
            UPDATE construction_stages
            SET
                name = :name,
                start_date = :startDate,
                end_date = :endDate,
                duration = :duration,
                durationUnit = :durationUnit,
                color = :color,
                externalId = :externalId,
                status = :status
            WHERE ID = :id
        ");

		
        $stmt->execute((array) $existingStage );

        return $this->getSingle($id);
    }


	 /**
     * Mark a construction stage as deleted.
     *
     * @param int $id The ID of the construction stage to mark as deleted.
     *
     * @return array Associative array with a message indicating the stage has been marked as deleted.
     */

    public function delete($id)
    {

        $existingStage = $this->getSingle($id);

        if (!$existingStage) {
            return ['error' => 'Construction stage not found'];
        }

        $stmt = $this->db->prepare("
            UPDATE construction_stages
            SET status = 'DELETED'
            WHERE ID = :id
        ");
        $stmt->execute(['id' => $id]);

        return ['message' => 'Construction stage marked as DELETED'];
    }

    /**
     * Validate construction stage data against specified rules.
     *
     * @param array $data Data to validate.
     *
     * @return array Associative array of validation errors, if any.
     */
	
	public function validateConstructionStageData($data)
    {

        $errors = [];

        // Validate 'name' field
        if (isset($data['name']) && strlen($data['name']) > 255) {
            $errors['name'] = 'Name must be a maximum of 255 characters in length.';
        }

        // Validate 'startDate' field
        if (isset($data['startDate']) && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $data['startDate'])) {
            $errors['startDate'] = 'Invalid startDate format. Use ISO8601 format (e.g., 2022-12-31T14:59:00Z).';
        }

        // Validate 'endDate' field
        if (isset($data['endDate']) && $data['endDate'] !== null) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $data['endDate'])) {
                $errors['endDate'] = 'Invalid endDate format. Use ISO8601 format (e.g., 2022-12-31T14:59:00Z).';
            } elseif (strtotime($data['endDate']) <= strtotime($data['startDate'])) {
                $errors['endDate'] = 'End date must be later than the start date.';
            }
        }

        // Validate 'durationUnit' field
        $validDurationUnits = ['HOURS', 'DAYS', 'WEEKS'];
        if (isset($data['durationUnit']) && !in_array($data['durationUnit'], $validDurationUnits)) {
            $errors['durationUnit'] = 'Invalid durationUnit. Must be one of HOURS, DAYS, WEEKS.';
        }

        // Validate 'color' field
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Invalid color format. Use a valid HEX color (e.g., #FF0000).';
        }

        // Validate 'externalId' field
        if (isset($data['externalId']) && strlen($data['externalId']) > 255) {
            $errors['externalId'] = 'External ID must be a maximum of 255 characters in length.';
        }

        // Validate 'status' field
        $validStatusValues = ['NEW', 'PLANNED', 'DELETED'];
        if (isset($data['status']) && !in_array($data['status'], $validStatusValues)) {
            $errors['status'] = 'Invalid status. Must be one of NEW, PLANNED, DELETED.';
        }

        // Return validation errors or an empty array if there are none
        return $errors;
    }


	 /**
     * Calculate the duration of a construction stage based on startDate, endDate, and durationUnit.
     *
     * @param ConstructionStagesCreate $data Data containing startDate, endDate, and durationUnit.
     *
     * @return float|null Calculated duration or null if not applicable.
     */
	
	private function calculateDuration($data)
	{
		
		if (!isset($data->endDate) || $data->endDate === null) {
			return null;
		}

		$startDate = new DateTime($data->startDate);
		$endDate = new DateTime($data->endDate);

	
		$durationUnit = isset($data->durationUnit) ? $data->durationUnit : 'DAYS';

		if ($durationUnit === 'HOURS') {
		
			$duration = $startDate->diff($endDate)->h;
		} elseif ($durationUnit === 'WEEKS') {
		
			$duration = $startDate->diff($endDate)->days / 7;
		} else {
	
			$duration = $startDate->diff($endDate)->days;
		}


		$duration = max(0, round($duration, 2));

		return $duration;
	}	
}