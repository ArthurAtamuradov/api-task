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

}