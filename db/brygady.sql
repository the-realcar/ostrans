INSERT INTO brygady (linia_id, nazwa, is_active) 
VALUES 
( (SELECT id FROM linie WHERE nr_linii='107' LIMIT 1), '107/1', true ),
( (SELECT id FROM linie WHERE nr_linii='107' LIMIT 1), '107/2', true ),
( (SELECT id FROM linie WHERE nr_linii='116' LIMIT 1), '116/1', true ),
( (SELECT id FROM linie WHERE nr_linii='116' LIMIT 1), '116/2', true );